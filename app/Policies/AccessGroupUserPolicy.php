<?php

namespace App\Policies;

use App\Models\AccessGroupUser;
use App\Models\User;

class AccessGroupUserPolicy {
    public function create(User $user): bool {
        return true;
    }

    public function update(User $user, AccessGroupUser $accessGroupUser): bool {
        return $accessGroupUser->accessGroup->user_id === $user->id;
    }

    public function delete(User $user, AccessGroupUser $accessGroupUser): bool {
        return $accessGroupUser->accessGroup->user_id === $user->id;
    }
}

