<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse as BaseFailedPasswordResetLinkRequestResponse;

class FailedPasswordResetLinkRequestResponse extends
    BaseFailedPasswordResetLinkRequestResponse {
    public function toResponse($request) {
        if ($this->status === Password::INVALID_USER) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => __($this->status),
                ]);
        }

        return back()->with(
            'session-message',
            SessionMessage::error(__($this->status))
        );
    }
}
