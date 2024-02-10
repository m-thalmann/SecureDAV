<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Responses\TwoFactorConfirmedResponse as BaseTwoFactorConfirmedResponse;

class TwoFactorConfirmedResponse extends BaseTwoFactorConfirmedResponse {
    public function toResponse(mixed $request): RedirectResponse {
        return back()
            ->withFragment('two-factor-authentication')
            ->with('two-factor-confirmed', true)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Two-factor authentication has been enabled.')
                )->forDuration()
            );
    }
}
