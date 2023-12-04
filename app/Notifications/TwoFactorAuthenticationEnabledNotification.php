<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorAuthenticationEnabledNotification extends Notification implements
    ShouldQueue {
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
            'title' => __('2FA enabled'),
            'body' => __(
                "Two-factor authentication has been enable on your account. If this wasn't you please check your account settings, change your password and contact us immediately."
            ),
        ];
    }
}

