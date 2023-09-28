<?php

namespace App\Policies;

use App\Models\FileVersion;
use App\Models\User;

class FileVersionPolicy {
    public function create(User $user): bool {
        return true;
    }

    public function update(User $user, FileVersion $fileVersion): bool {
        return $fileVersion->file->user_id === $user->id;
    }

    public function delete(User $user, FileVersion $fileVersion): bool {
        return $fileVersion->file->user_id === $user->id;
    }

    public function restore(User $user, FileVersion $fileVersion): bool {
        return $fileVersion->file->user_id === $user->id;
    }

    public function forceDelete(User $user, FileVersion $fileVersion): bool {
        return $fileVersion->file->user_id === $user->id;
    }
}

