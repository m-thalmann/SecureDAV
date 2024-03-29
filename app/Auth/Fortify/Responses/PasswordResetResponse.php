<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Responses\PasswordResetResponse as BasePasswordResetResponse;

class PasswordResetResponse extends BasePasswordResetResponse {
    public function toResponse(mixed $request): RedirectResponse {
        return redirect()
            ->route('login')
            ->with(
                'session-message',
                SessionMessage::success(__($this->status))
            );
    }
}
