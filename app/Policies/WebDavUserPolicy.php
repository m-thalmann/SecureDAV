<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebDavUser;
use Illuminate\Auth\Access\Response;

class WebDavUserPolicy {
    public function viewAny(User $user): Response {
        return Response::allow();
    }

    public function view(User $user, WebDavUser $webDavUser): Response {
        return $webDavUser->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): Response {
        return Response::allow();
    }

    public function update(User $user, WebDavUser $webDavUser): Response {
        return $webDavUser->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, WebDavUser $webDavUser): Response {
        return $webDavUser->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
