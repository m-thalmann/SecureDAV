<?php

namespace App\Policies;

use App\Models\AccessGroupUser;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccessGroupUserPolicy {
    public function create(User $user): Response {
        return Response::allow();
    }

    public function update(
        User $user,
        AccessGroupUser $accessGroupUser
    ): Response {
        return $accessGroupUser->accessGroup->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(
        User $user,
        AccessGroupUser $accessGroupUser
    ): Response {
        return $accessGroupUser->accessGroup->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

