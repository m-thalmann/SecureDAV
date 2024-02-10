<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Responses\TwoFactorDisabledResponse as BaseTwoFactorDisabledResponse;

class TwoFactorDisabledResponse extends BaseTwoFactorDisabledResponse {
    public function toResponse(mixed $request): RedirectResponse {
        return back()
            ->withFragment('two-factor-authentication')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Two-factor authentication has been disabled.')
                )->forDuration()
            );
    }
}
