<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPolicy {
    public function viewAny(User $user): Response {
        return Response::allow();
    }

    public function update(
        User $user,
        DatabaseNotification $notification
    ): Response {
        return $this->isSameUser($user, $notification)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(
        User $user,
        DatabaseNotification $notification
    ): Response {
        return $this->isSameUser($user, $notification)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    protected function isSameUser(
        User $user,
        DatabaseNotification $notification
    ): bool {
        $notifiable = $notification->notifiable;

        return $notifiable instanceof User && $notifiable->id === $user->id;
    }
}

