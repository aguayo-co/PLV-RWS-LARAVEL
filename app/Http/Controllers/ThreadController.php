<?php

namespace App\Http\Controllers;

use App\Http\Traits\MessagesFilter;
use App\Message;
use App\Notifications\NewMessage;
use App\Participant;
use App\Thread;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ThreadController extends Controller
{
    use MessagesFilter;

    protected $modelClass = Thread::class;
    public static $allowedWhereIn = ['product_id'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware(self::class . '::checkThreadPrivacy')->only('show');
    }

    /**
     * Middleware that validates permissions to access a thread.
     */
    public static function checkThreadPrivacy($request, $next)
    {
        $thread = $request->route()->parameters['thread'];
        if (!$thread->private) {
            return $next($request);
        }

        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if ($thread->hasParticipant($user->id)) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'This is a private thread.');
    }

    protected function validate(array $data, Model $thread = null)
    {
        if (!$thread) {
            $this->validateUniquePrivateThread($data);
        }

        parent::validate($data, $thread);
    }

    protected function validateUniquePrivateThread($data)
    {
        $recipients = data_get($data, 'recipients');

        if (!$recipients) {
            return;
        }

        if (count($recipients) > 1) {
            return;
        }

        if (!data_get($data, 'private')) {
            return;
        }

        $threads = Thread::where('private', true)->whereNull('product_id')
            ->between([auth()->id(), array_first($recipients)])
            ->count();

        if ($threads > 0) {
            throw ValidationException::withMessages([
                'recipients' => [__('A thread with the given recipient already exists.')],
            ]);
        }
    }

    protected function validationRules(array $data, ?Model $thread)
    {
        $required = !$thread ? 'required|' : '';
        $requiredRecipients = !$thread ? 'required_without:product_id|' : '';
        return [
            'subject' => $required . 'string',
            'private' => $required . 'boolean|empty_with:product_id',
            'product_id' => 'integer|empty_with:private|exists:products,id',
            'body' => [
                trim($required, '|'),
                'string',
                'max:10000',
                $this->bodyFilterRule()
            ],
            'recipients' => $requiredRecipients . 'array',
            'recipients.*' => 'integer|exists:users,id,deleted_at,NULL|not_in:' . auth()->id(),
        ];
    }

    /**
     * Filters the index query.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        return function ($query) {
            $query = $query->latest('updated_at');

            // If asked for a conversation with other User.
            $recipientId = array_get(request()->query('filter'), 'private_with');
            if ($recipientId && auth()->id()) {
                $between = [auth()->id(), $recipientId];
                return $query->whereNull('product_id')->where('private', true)->between($between);
            }

            // If asked for a product's threads, only show public ones.
            $productId = array_has(request()->query('filter'), 'product_id');
            if ($productId) {
                return $query->where('private', false);
            }

            $filterUnread = (bool) array_get(request()->query('filter'), 'unread');
            // All threads that user is participating in that have
            // unread messages.
            if ($filterUnread) {
                return $query->forUserWithNewMessages(auth()->id())->latest('updated_at');
            }

            // All threads that user is participating in.
            return $query->forUser(auth()->id());
        };
    }

    /**
     * Return a thread and mark it as read by current user.
     */
    public function show(Request $request, Model $thread)
    {
        $thread = parent::show($request, $thread);
        $userId = auth()->id();
        if ($userId) {
            $thread->markAsRead($userId);
        }

        return $thread;
    }

    /**
     * Stores data related to the new thread.
     */
    public function postStore(Request $request, Model $thread)
    {
        // Message
        Message::create([
            'thread_id' => $thread->id,
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);

        // Sender
        Participant::create([
            'thread_id' => $thread->id,
            'user_id' => auth()->id(),
            'last_read' => now(),
        ]);

        // Recipients
        if ($request->recipients) {
            $thread->addParticipant($request->recipients);
            $recipients = User::whereIn('id', $request->recipients)->get();
            foreach ($recipients as $recipient) {
                $recipient->notify(new NewMessage(['thread' => $thread]));
            }
        }

        return parent::postStore($request, $thread);
    }

    protected function setVisibility(Collection $collection)
    {
        $collection->load('messages', 'participants.user', 'trashedParticipants.user');
        $this->setUnreadForCurrentUser($collection);
    }

    protected function setUnreadForCurrentUser(Collection $collection)
    {
        $loggedUser = auth()->user();
        if (!$loggedUser) {
            return;
        }

        $collection->each(function ($thread) use ($loggedUser) {
            $thread->participants->each(function ($participant) use ($loggedUser) {
                if ($loggedUser->is($participant->user)) {
                    $participant->user->append(['unread_count']);
                }
            });
        });
    }
}
