<?php

namespace App\Auth\Fortify\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation as UpdatesUserProfileInformationContract;

class UpdatesUserProfileInformation implements
    UpdatesUserProfileInformationContract {
    public function update(User $user, array $input): void {
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
            ])->validateWithBag('updateProfileInformation');
        } catch (ValidationException $e) {
            $e->redirectTo(
                back()
                    ->withFragment('#update-information')
                    ->getTargetUrl()
            );

            throw $e;
        }

        if (
            config('app.email_verification_enabled') &&
            $input['email'] !== $user->email
        ) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user
                ->forceFill([
                    'name' => $input['name'],
                    'email' => $input['email'],
                ])
                ->save();
        }
    }

    protected function updateVerifiedUser(User $user, array $input): void {
        $user
            ->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'email_verified_at' => null,
            ])
            ->save();

        $user->sendEmailVerificationNotification();
    }
}
