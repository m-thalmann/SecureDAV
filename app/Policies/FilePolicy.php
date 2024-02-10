<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FilePolicy {
    public function view(User $user, File $file): Response {
        return $file->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): Response {
        return Response::allow();
    }

    public function update(User $user, File $file): Response {
        return $file->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, File $file): Response {
        return $file->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function restore(User $user, File $file): Response {
        return $file->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function forceDelete(User $user, File $file): Response {
        return $file->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
