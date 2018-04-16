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

    public function view($view, array $data = [])
    {
        parent::view($view, $data);
        $this->subject = view($view . '-subject', $this->data());
        return $this;
    }

    public function data()
    {
        return array_merge(parent::data(), ['user' => $this->user]);
    }
}
