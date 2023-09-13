<?php

namespace App\Auth\Fortify\Actions;

use App\Auth\Fortify\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords as UpdatesUserPasswordsContract;

class UpdatesUserPasswords implements UpdatesUserPasswordsContract {
    use PasswordValidationRules;

    public function update(User $user, array $input): void {
        try {
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
        } catch (ValidationException $e) {
            $e->redirectTo(
                back()
                    ->withFragment('update-password')
                    ->getTargetUrl()
            );

            throw $e;
        }

        $user
            ->forceFill([
                'password' => Hash::make($input['password']),
            ])
            ->save();
    }
}

