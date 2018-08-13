<?php

namespace App\Http\Controllers;

use App\Http\Traits\MessagesFilter;
use App\Message;
use App\Notifications\NewMessage;
use App\Participant;
use App\Thread;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    use MessagesFilter;
    protected $modelClass = Message::class;

    public function __construct()
    {
        parent::__construct();
        // The owner_or_admin access control to `store` method
        // checks against the parent thread which comes in the URL.
        $this->middleware('owner_or_admin')->only('store');
    }

    protected function validationRules(array $data, ?Model $message)
    {
        $required = !$message ? 'required|' : '';
        return [
            'body' => [
                trim($required, '|'),
                'string',
                'max:10000',
                $this->bodyFilterRule()
            ],
            'recipients' => 'array',
            'recipients.*' => 'integer|exists:users,id,deleted_at,NULL|not_in:' . auth()->id(),
        ];
    }

    protected function alterFillData($data, Model $message = null)
    {
        // Remove 'user_id' from $data.
        array_forget($data, 'user_id');
        if (!$message) {
            $data['user_id'] = auth()->id();
        }

        // Remove 'thread_id' from $data.
        array_forget($data, 'thread_id');
        if (!$message) {
            $thread = request()->route('thread');
            $data['thread_id'] = $thread->id;
        }

        return $data;
    }

    /**
     * Adds a new message to a current thread.
     *
     * @param $id
     * @return mixed
     */
    public function postStore(Request $request, Model $message)
    {
        $thread = $message->thread;
        $thread->activateAllParticipants();

        // Add replier as a participant
        $participant = Participant::firstOrCreate([
            'thread_id' => $thread->id,
            'user_id' => auth()->id(),
        ]);
        $participant->last_read = now();
        $participant->save();

        // Recipients
        if ($request->recipients) {
            $thread->addParticipant($request->recipients);
            $recipients = User::whereIn('id', $request->recipients)->get();
            foreach ($recipients as $recipient) {
                $recipient->notify(new NewMessage(['thread' => $thread]));
            }
        }

        return parent::postStore($request, $message);
    }
}
