<?php

namespace App\Auth\Fortify\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Laravel\Fortify\Contracts\ResetsUserPasswords as ResetsUserPasswordsContract;

class ResetsUserPasswords implements ResetsUserPasswordsContract {
    public function reset(User $user, array $input): void {
        Validator::make($input, [
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ])->validate();

        $user
            ->forceFill([
                'password' => Hash::make($input['password']),
            ])
            ->save();
    }
}
