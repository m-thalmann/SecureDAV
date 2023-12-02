<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserDeletedNotification extends Notification implements ShouldQueue {
    use Queueable;

    public function __construct(public readonly array $userData) {
    }

    public function via(object $notifiable): array {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage {
        $data = $this->toArray($notifiable);

        return (new MailMessage())
            ->subject($data['title'])
            ->line($data['body']);
    }

    public function toArray(object $notifiable): array {
        return [
            'title' => __('Account deleted'),
            'body' => __(
                'Your account, including any data and files, has been successfully deleted. This action can not be undone.'
            ),
        ];
    }
}

