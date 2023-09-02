<x-guest-layout :title="__('Reset password')">
    <x-auth-card>
        <x-session-message :message="session('session-message')" class="mb-3"></x-session-message>

        <form method="POST" action="{{ route('password.update') }}" class="w-full">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="form-control w-full">
                <label class="label" for="email">
                    <span class="label-text">{{ __('Email') }}</span>
                </label>
                <input id="email" type="email" name="email" class="input input-md w-full{{ $errors->get('email') ? ' input-error' : '' }}" value="{{ old('email', $request->email) }}" required autocomplete="username" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->get('email')" />
                    </span>
                </label>
            </div>

            <div class="form-control w-full">
                <label class="label" for="password">
                    <span class="label-text">{{ __('Password') }}</span>
                </label>
                <input id="password" type="password" name="password" class="input input-md w-full{{ $errors->get('password') ? ' input-error' : '' }}" required autofocus autocomplete="new-password" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->get('password')" />
                    </span>
                </label>
            </div>

            <div class="form-control w-full">
                <label class="label" for="password-confirmation">
                    <span class="label-text">{{ __('Confirm Password') }}</span>
                </label>
                <input id="password-confirmation" type="password" name="password_confirmation" class="input input-md w-full{{ $errors->get('password_confirmation') ? ' input-error' : '' }}" required autocomplete="new-password" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->get('password_confirmation')" />
                    </span>
                </label>
            </div>

            <div class="card-actions justify-end items-center gap-6 mt-6">
                <input type="submit" value="{{ __('Reset Password') }}" class="btn btn-secondary max-sm:w-full max-sm:text-xs" />
            </div>
        </form>
    </x-auth-card>

    <a href="{{ route('login') }}" class="link link-hover mt-2">&larr; {{ __('Back to login') }}</a>
</x-guest-layout>