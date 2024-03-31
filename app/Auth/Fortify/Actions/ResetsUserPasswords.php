<?php

namespace App\Auth\Fortify\Actions;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords as ResetsUserPasswordsContract;

class ResetsUserPasswords implements ResetsUserPasswordsContract {
    use PasswordValidationRules;

    public function reset(User $user, array $input): void {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user
            ->forceFill([
                'password' => Hash::make($input['password']),
            ])
            ->save();
    }
}
