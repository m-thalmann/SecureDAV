<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WebDavSuspendedNotification extends Notification implements ShouldQueue {
    use Queueable;

    public function __construct(public readonly User $user) {
    }

    public function via(object $notifiable): array {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage {
        $data = $this->toArray($notifiable);

        return (new MailMessage())
            ->subject($data['title'])
            ->line($data['body']);
    }

    public function toArray(object $notifiable): array {
        return [
            'title' => __('WebDav suspended'),
            'body' => __(
                "Your account's WebDav access has been suspended. If this wasn't you please check your account settings, change your password and contact us immediately."
            ),
        ];
    }
}

