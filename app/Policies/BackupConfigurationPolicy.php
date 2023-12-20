<?php

namespace App\Policies;

use App\Models\BackupConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BackupConfigurationPolicy {
    public function viewAny(User $user): Response {
        return Response::allow();
    }
}

