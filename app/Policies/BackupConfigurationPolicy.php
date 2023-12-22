<?php

namespace App\Policies;

use App\Models\BackupConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BackupConfigurationPolicy {
    public function viewAny(User $user): Response {
        return Response::allow();
    }

    public function view(
        User $user,
        BackupConfiguration $backupConfiguration
    ): Response {
        return $backupConfiguration->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(
        User $user,
        BackupConfiguration $backupConfiguration
    ): Response {
        return $backupConfiguration->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

