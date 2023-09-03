<?php

namespace App\Auth\Fortify\Actions;

use App\Auth\Fortify\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords as UpdatesUserPasswordsContract;

class UpdatesUserPasswords implements UpdatesUserPasswordsContract {
    use PasswordValidationRules;

    public function update(User $user, array $input): void {
        Validator::make(
            $input,
            [
                'current_password' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
                'password' => $this->passwordRules(),
            ],
            [
                'current_password.current_password' => __(
                    'The provided password does not match your current password.'
                ),
            ]
        )->validateWithBag('updatePassword');

        $user
            ->forceFill([
                'password' => Hash::make($input['password']),
            ])
            ->save();
    }
}
