<?php

namespace App\Policies;

use App\Models\Directory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DirectoryPolicy {
    public function view(User $user, Directory $directory): Response {
        return $directory->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): Response {
        return Response::allow();
    }

    public function update(User $user, Directory $directory): Response {
        return $directory->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Directory $directory): Response {
        return $directory->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
