<?php

namespace App\Auth\Fortify\Actions;

use App\Auth\Fortify\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers as CreatesNewUsersContract;

class CreatesNewUsers implements CreatesNewUsersContract {
    use PasswordValidationRules;

    public function create(array $input): User {
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::make($data);
        $user->encryption_key = Str::random(16);

        $user->save();

        return $user;
    }
}
