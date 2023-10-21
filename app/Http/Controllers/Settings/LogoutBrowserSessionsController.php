<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogoutBrowserSessionsController extends Controller {
    public function __invoke(Request $request): RedirectResponse {
        if (config('session.driver') !== 'database') {
            return back()
                ->withFragment('browser-sessions')
                ->with(
                    'session-message[browser-sessions]',
                    SessionMessage::error(__('This feature is not available.'))
                );
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->delete();

        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Logged out of all browser sessions.')
                )->forDuration()
            );
    }
}
