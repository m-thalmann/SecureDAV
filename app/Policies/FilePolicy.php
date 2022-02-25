<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy {
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, File $file) {
        return $user->id === $file->user_id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, File $file) {
        return $user->id === $file->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, File $file) {
        return $user->id === $file->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, File $file) {
        return $user->id === $file->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, File $file) {
        return $user->id === $file->user_id;
    }
}
