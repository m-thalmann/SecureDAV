<?php

namespace App\Policies;

use App\Models\AccessUser;
use App\Models\User;

class AccessUserPolicy {
    public function viewAny(User $user): bool {
        return true;
    }

    public function view(User $user, AccessUser $accessUser): bool {
        return $accessUser->user_id === $user->id;
    }

    public function create(User $user): bool {
        return true;
    }

    public function update(User $user, AccessUser $accessUser): bool {
        return $accessUser->user_id === $user->id;
    }

    public function delete(User $user, AccessUser $accessUser): bool {
        return $accessUser->user_id === $user->id;
    }
}

