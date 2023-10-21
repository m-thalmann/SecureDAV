<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Responses\FailedPasswordResetResponse as BaseFailedPasswordResetResponse;

class FailedPasswordResetResponse extends BaseFailedPasswordResetResponse {
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
