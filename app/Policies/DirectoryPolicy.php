<?php

namespace App\Policies;

use App\Models\Directory;
use App\Models\User;

class DirectoryPolicy {
    public function viewAny(User $user): bool {
        return false;
    }

    public function view(User $user, Directory $directory): bool {
        return $directory->user_id === $user->id;
    }

    public function create(User $user): bool {
        return true;
    }

    public function update(User $user, Directory $directory): bool {
        return $directory->user_id === $user->id;
    }

    public function delete(User $user, Directory $directory): bool {
        return $directory->user_id === $user->id;
    }

    public function restore(User $user, Directory $directory): bool {
        return $directory->user_id === $user->id;
    }
}

