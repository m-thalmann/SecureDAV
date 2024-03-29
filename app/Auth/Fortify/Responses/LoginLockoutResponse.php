<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Responses\LockoutResponse as BaseLockoutResponse;

class LoginLockoutResponse extends BaseLockoutResponse {
    public function toResponse(mixed $request): RedirectResponse {
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
