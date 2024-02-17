<?php

namespace App\Http\Controllers\Settings;

use App\Events\UserDeleted;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SessionMessage;
use Carbon\Carbon;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jenssegers\Agent\Agent;

class ProfileSettingsController extends Controller {
    public function show(Request $request): View {
        $twoFactorEnabled = authUser()->two_factor_secret !== null;
        $twoFactorConfirmed =
            $twoFactorEnabled && authUser()->two_factor_confirmed_at !== null;

        return view('settings.profile.show', [
            'user' => authUser(),
            'availableTimezones' => timezone_identifiers_list(),
            'twoFactorEnabled' => $twoFactorEnabled,
            'twoFactorConfirmed' => $twoFactorConfirmed,
            'sessions' => $this->getSessions($request)?->all(),
        ]);
    }

    public function destroy(): RedirectResponse {
        $user = authUser();

        if (
            $user->is_admin &&
            User::query()
                ->whereNot('id', $user->id)
                ->where('is_admin', true)
                ->count() === 0
        ) {
            return back()
                ->withFragment('delete-account')
                ->with(
                    'session-message[delete-account]',
                    SessionMessage::error(
                        __('You cannot delete the last admin account.')
                    )
                );
        }

        $user->delete();

        /**
         * @var SessionGuard
         */
        $guard = Auth::guard('web');
        $guard->forgetUser();

        session()->invalidate();
        session()->regenerateToken();

        event(new UserDeleted($user));

        return redirect()
            ->route('login')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Your account has been deleted.')
                )->forDuration()
            );
    }

    /**
     * Get the user's current sessions
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Collection|null
     */
    protected function getSessions(Request $request): ?Collection {
        if (config('session.driver') !== 'database') {
            return null;
        }

        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', authUser()->getAuthIdentifier())
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function (mixed $session) use ($request) {
                $agent = new Agent();
                $agent->setUserAgent($session->user_agent);

                return (object) [
                    'agent' => (object) [
                        'isDesktop' => $agent->isDesktop(),
                        'platform' => $agent->platform() ?: __('Unknown'),
                        'browser' => $agent->browser() ?: __('Unknown'),
                    ],
                    'ipAddress' => $session->ip_address,
                    'isCurrentDevice' =>
                        $session->id === $request->session()->getId(),
                    'lastActive' => Carbon::createFromTimestamp(
                        $session->last_activity
                    )->diffForHumans(),
                ];
            });
    }
}
