<?php

namespace App\Notifications;

use App\Notifications\Messages\UserMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrilovNotification extends Notification
{
    use Queueable;

    public $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new UserMailMessage($notifiable))->view(
            'email.' . snake_case(class_basename(get_class($this))),
            $this->data
        );
    }
}
