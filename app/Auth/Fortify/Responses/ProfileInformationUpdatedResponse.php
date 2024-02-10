<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Responses\ProfileInformationUpdatedResponse as BaseProfileInformationUpdatedResponse;

class ProfileInformationUpdatedResponse extends
    BaseProfileInformationUpdatedResponse {
    public function toResponse(mixed $request): RedirectResponse {
        return back()
            ->withFragment('update-information')
            ->with(
                'snackbar',
                SessionMessage::success(__('Profile updated'))->forDuration()
            );
    }
}
