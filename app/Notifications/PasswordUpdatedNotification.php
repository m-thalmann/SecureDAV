<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordUpdatedNotification extends Notification implements ShouldQueue {
    use Queueable;

    public function __construct(public readonly User $user) {
    }

    public function via(object $notifiable): array {
        return ['mail', 'database'];
    }

    public function viaConnections(): array {
        return [
            'database' => 'sync',
        ];
    }

    public function toMail(object $notifiable): MailMessage {
        $data = $this->toArray($notifiable);

        return (new MailMessage())
            ->subject($data['title'])
            ->line($data['body'])
            ->action('Check settings', route('settings.index'));
    }

    public function toArray(object $notifiable): array {
        return [
            'title' => __('Password updated'),
            'body' => __(
                "We have received a request to update your account's password. If you did not request this change, please check your account settings, change your password and contact us immediately."
            ),
        ];
    }
}

