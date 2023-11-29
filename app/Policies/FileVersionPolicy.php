<?php

namespace App\Policies;

use App\Models\FileVersion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FileVersionPolicy {
    public function create(User $user): Response {
        return Response::allow();
    }

    public function view(User $user, FileVersion $fileVersion): Response {
        return $fileVersion->file->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, FileVersion $fileVersion): Response {
        return $fileVersion->file->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, FileVersion $fileVersion): Response {
        return $fileVersion->file->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

