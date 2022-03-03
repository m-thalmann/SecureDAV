<?php

namespace App\Policies;

use App\Models\AccessUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccessUserPolicy {
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccessUser  $accessUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AccessUser $accessUser) {
        return $user->id === $accessUser->user_id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccessUser  $accessUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, AccessUser $accessUser) {
        return $user->id === $accessUser->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccessUser  $accessUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, AccessUser $accessUser) {
        return $user->id === $accessUser->user_id;
    }
}
