<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\View\Helpers\SessionMessage;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileSettingsController extends Controller {
    public function show(Request $request): View {
        return view('settings.profile.show', [
            'user' => $request->user(),
        ]);
    }

    public function destroy(Request $request): RedirectResponse {
        if (!$request->user()->delete()) {
            return back()
                ->withFragment('#delete-account')
                ->with(
                    'session-message[delete-account]',
                    SessionMessage::error(__('Failed to delete your account.'))
                );
        }

        /**
         * @var SessionGuard
         */
        $guard = Auth::guard('web');
        $guard->forgetUser();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Your account has been deleted.'),
                    duration: 5
                )
            );
    }
}

