<?php

namespace App\Policies;

use App\Models\AccessUserToken;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccessUserTokenPolicy {
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccessUserToken  $accessUserToken
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AccessUserToken $accessUserToken) {
        return $user->id === $accessUserToken->accessUser()->user_id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccessUserToken  $accessUserToken
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, AccessUserToken $accessUserToken) {
        return $user->id === $accessUserToken->accessUser()->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccessUserToken  $accessUserToken
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, AccessUserToken $accessUserToken) {
        return $user->id === $accessUserToken->accessUser()->user_id;
    }
}
