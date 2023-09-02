<?php

namespace App\Auth\Fortify\Responses;

use App\View\Helpers\SessionMessage;
use Laravel\Fortify\Http\Responses\LockoutResponse as BaseLockoutResponse;

class LoginLockoutResponse extends BaseLockoutResponse {
    public function toResponse($request) {
        $seconds = $this->limiter->availableIn($request);

        return back()
            ->withInput($request->only('email'))
            ->with(
                'session-message',
                SessionMessage::error(
                    __('auth.throttle', [
                        'seconds' => $seconds,
                        'minutes' => ceil($seconds / 60),
                    ])
                )
            );
    }
}
