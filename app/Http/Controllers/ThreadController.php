<?php

namespace App\Http\Controllers;

use App\Thread;
use App\User;
use Carbon\Carbon;
use Cmgmyr\Messenger\Models\Message;
use Cmgmyr\Messenger\Models\Participant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThreadController extends Controller
{
    protected $modelClass = Thread::class;
    public static $allowedWhereIn = ['product_id'];

    public function __construct()
    {
        parent::__construct();
        // Add owner_or_admin access control to `show` method.
        $this->middleware('owner_or_admin')->only('show');
    }

    protected function validationRules(array $data, ?Model $thread)
    {
        $required = !$thread ? 'required|' : '';
        return [
            'subject' => $required . 'string',
            'private' => $required . 'boolean',
            'product_id' => 'integer|exists:products,id',
            'body' => $required . 'string',
            'recipients' => $required . 'array',
            'recipients.*' => 'integer|exists:users,id',
        ];
    }

    /**
     * Return a Closure that modifies the index query.
     * The closure receives the $query as a parameter.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        return function ($query) {
            if (!request()->query('product_id')) {
                $filterUnread = (bool) array_get(request()->query('filter'), 'unread');

                if ($filterUnread) {
                    // All threads that user is participating in, with new messages
                    $query = $query->forUserWithNewMessages(auth()->id())->latest('updated_at');
                }

                if (!$filterUnread) {
                    // All threads that user is participating in
                    $query = $query->forUser(auth()->id());
                }
            }

            return $query->latest('updated_at');
        };
    }

    /**
     * Return a thread and mark it as read by current user.
     */
    public function show(Request $request, Model $thread)
    {
        $thread = parent::show($request, $thread);
        if ($userId = auth()->id()) {
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
        }

        return parent::postStore($request, $thread);
    }
}
