<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Responses\PasswordUpdateResponse as BasePasswordUpdateResponse;

class PasswordUpdateResponse extends BasePasswordUpdateResponse {
    public function toResponse(mixed $request): RedirectResponse {
        return back()
            ->withFragment('update-password')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Password updated successfully.')
                )->forDuration()
            );
    }
}
