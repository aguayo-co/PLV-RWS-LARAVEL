<?php

namespace App\Notifications\Messages;

use Illuminate\Notifications\Messages\MailMessage;
use App\User;

class UserMailMessage extends MailMessage
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Set the view for the subject and for the body of the email.
     *
     * The view for the subject is the same as the one form the
     * body with "-subject" appended at the end.
     */
    public function view($view, array $data = [])
    {
        parent::view($view, $data);
        $this->subject = view($view . '-subject', $this->data());
        return $this;
    }

    /**
     * Append user to email data.
     */
    public function data()
    {
        return array_merge(parent::data(), ['user' => $this->user]);
    }
}
