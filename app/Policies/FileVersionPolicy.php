<?php

namespace App\Policies;

use App\Models\FileVersion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FileVersionPolicy {
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FileVersion  $fileVersion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, FileVersion $fileVersion) {
        return $user->id === $fileVersion->file->user_id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FileVersion  $fileVersion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FileVersion $fileVersion) {
        return $user->id === $fileVersion->file->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FileVersion  $fileVersion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, FileVersion $fileVersion) {
        return $user->id === $fileVersion->file->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FileVersion  $fileVersion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, FileVersion $fileVersion) {
        return $user->id === $fileVersion->file->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FileVersion  $fileVersion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, FileVersion $fileVersion) {
        return $user->id === $fileVersion->file->user_id;
    }
}
