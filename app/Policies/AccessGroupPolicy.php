<?php

namespace App\Policies;

use App\Models\AccessGroup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccessGroupPolicy {
    public function viewAny(User $user): Response {
        return Response::allow();
    }

    public function view(User $user, AccessGroup $accessGroup): Response {
        return $accessGroup->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): Response {
        return Response::allow();
    }

    public function update(User $user, AccessGroup $accessGroup): Response {
        return $accessGroup->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, AccessGroup $accessGroup): Response {
        return $accessGroup->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

