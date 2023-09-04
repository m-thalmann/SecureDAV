<?php

namespace App\Auth\Fortify\Actions;

use App\Auth\Fortify\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords as ResetsUserPasswordsContract;

class ResetsUserPasswords implements ResetsUserPasswordsContract {
    use PasswordValidationRules;

    public function reset(User $user, array $input): void {
        try {
            Validator::make($input, [
                'password' => $this->passwordRules(),
            ])->validate();
        } catch (ValidationException $e) {
            $e->redirectTo(
                back()
                    ->withFragment('#delete-account')
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
