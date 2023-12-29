<?php

namespace App\Notifications;

use App\Models\BackupConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupFailedNotification extends Notification implements ShouldQueue {
    use Queueable;

    public function __construct(
        public readonly BackupConfiguration $backupConfiguration
    ) {
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

        $message = (new MailMessage())
            ->subject($data['title'])
            ->line($data['body'])
            ->action($data['action']['name'], $data['action']['url']);

        return $message;
    }

    public function toArray(object $notifiable): array {
        return [
            'title' => __('Backup failed'),
            'body' => __(
                'A backup of your files has failed. Please check your backup configuration and try again.'
            ),
            'action' => [
                'name' => __('Go to backup'),
                'url' => route('backups.show', $this->backupConfiguration),
            ],
        ];
    }
}

