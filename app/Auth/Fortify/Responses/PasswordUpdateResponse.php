<?php

namespace App\Auth\Fortify\Responses;

use App\View\Helpers\SessionMessage;
use Laravel\Fortify\Http\Responses\PasswordUpdateResponse as BasePasswordUpdateResponse;

class PasswordUpdateResponse extends BasePasswordUpdateResponse {
    public function toResponse($request) {
        return back()
            ->withFragment('update-password')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Password updated successfully.'),
                    duration: 5
                )
            );
    }
}
