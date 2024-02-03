<?php

namespace App\Auth\Fortify\Actions;

use App\Events\EmailUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation as UpdatesUserProfileInformationContract;

class UpdatesUserProfileInformation implements
    UpdatesUserProfileInformationContract {
    public function update(User $user, array $input): void {
        $allowedTimezones = timezone_identifiers_list();
        $allowedTimezones[] = 'default';

        try {
            Validator::make($input, [
                'name' => ['required', 'string', 'max:255'],

                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],

                'timezone' => [
                    'required',
                    'string',
                    Rule::in($allowedTimezones),
                ],
            ])->validateWithBag('updateProfileInformation');
        } catch (ValidationException $e) {
            $e->redirectTo(
                back()
                    ->withFragment('update-information')
                    ->getTargetUrl()
            );

            throw $e;
        }

        $newEmail = $input['email'] !== $user->email;

        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'timezone' =>
                $input['timezone'] === 'default' ? null : $input['timezone'],
        ]);

        if (config('app.email_verification_enabled') && $newEmail) {
            $user->forceFill([
                'email_verified_at' => null,
            ]);
        }

        $user->save();

        if ($newEmail) {
            event(new EmailUpdated($user));

            if (config('app.email_verification_enabled')) {
                $user->sendEmailVerificationNotification();
            }
        }
    }
}
