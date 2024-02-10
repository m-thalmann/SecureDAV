<?php

namespace App\Auth\Fortify\Actions;

use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication as BaseConfirmTwoFactorAuthentication;

class ConfirmTwoFactorAuthentication extends
    BaseConfirmTwoFactorAuthentication {
    public function __invoke(mixed $user, mixed $code): void {
        try {
            parent::__invoke($user, $code);
        } catch (ValidationException $e) {
            $e->redirectTo(
                back()
                    ->withFragment('two-factor-authentication')
                    ->getTargetUrl()
            );

            throw $e;
        }
    }
}
