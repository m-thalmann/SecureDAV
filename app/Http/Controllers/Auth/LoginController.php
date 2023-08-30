<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\View\Helpers\SessionMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller {
    const MAX_ATTEMPTS = 5;

    public function create(): View {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse {
        $throttleKey = "login-{$request->ip()}";

        if (RateLimiter::tooManyAttempts($throttleKey, static::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

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

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            return back()
                ->withInput($request->only('email'))
                ->with(
                    'session-message',
                    SessionMessage::warning(__('auth.failed'))
                );
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}

