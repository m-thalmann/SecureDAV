<?php

namespace App\Policies;

use App\Models\AccessGroup;
use App\Models\User;

class AccessGroupPolicy {
    public function viewAny(User $user): bool {
        return true;
    }

    public function view(User $user, AccessGroup $accessGroup): bool {
        return $accessGroup->user_id === $user->id;
    }

    public function create(User $user): bool {
        return true;
    }

    public function update(User $user, AccessGroup $accessGroup): bool {
        return $accessGroup->user_id === $user->id;
    }

    public function delete(User $user, AccessGroup $accessGroup): bool {
        return $accessGroup->user_id === $user->id;
    }
}

