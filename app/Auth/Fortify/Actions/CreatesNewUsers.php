<?php

namespace App\Auth\Fortify\Actions;

use App\Actions\CreateUser;
use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers as CreatesNewUsersContract;

class CreatesNewUsers implements CreatesNewUsersContract {
    public function __construct(protected CreateUser $createUserAction) {
    }

    public function create(array $input): User {
        return $this->createUserAction->handle(
            $input['name'],
            $input['email'],
            $input['password'],
            $input['password_confirmation'],
            isAdmin: false
        );
    }
}
