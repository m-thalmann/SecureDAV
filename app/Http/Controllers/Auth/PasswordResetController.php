<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\View\Helpers\SessionMessage;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class PasswordResetController extends Controller {
    /* Password reset-email request */

    public function create(): View {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse {
        $email = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($email);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with(
                'session-message',
                SessionMessage::success(__($status))
            );
        }
        if ($status === Password::INVALID_USER) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => __($status),
                ]);
        }

        return back()->with(
            'session-message',
            SessionMessage::error(__($status))
        );
    }

    /* Reset password */

    public function edit(Request $request): View {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function update(Request $request): RedirectResponse {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function (User $user) use ($request) {
                $user
                    ->forceFill([
                        'password' => Hash::make($request->password),
                        'remember_token' => Str::random(60),
                    ])
                    ->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('session-message', SessionMessage::success(__($status)));
        }
        if ($status === Password::INVALID_USER) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => __($status),
                ]);
        }

        return back()->with(
            'session-message',
            SessionMessage::error(__($status))
        );
    }
}

