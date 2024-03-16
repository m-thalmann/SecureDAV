<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification {
    public function __construct(public readonly User $user) {
    }

    public function via(object $notifiable): array {
        return ['database'];
    }

    public function toArray(object $notifiable): array {
        return [
            'title' => __('Welcome!'),
            'body' => __(
                "Welcome to SecureDav, we're glad to have you on board! " .
                    'To finish setting up make sure you set your correct timezone in the settings and enable 2FA for better security.'
            ),
        ];
    }
}
