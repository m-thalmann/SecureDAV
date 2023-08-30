<x-guest-layout :title="__('Reset password')">
    <x-auth-card>
        <x-session-message :message="session('session-message')" class="mb-3"></x-session-message>

        <div class="text-left text-sm">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="w-full">
            @csrf

            <div class="form-control w-full">
                <label class="label" for="email">
                    <span class="label-text">{{ __('Email') }}</span>
                </label>
                <input id="email" type="email" name="email" class="input input-md w-full{{ $errors->get('email') ? ' input-error' : '' }}" value="{{ old('email') }}" required autofocus />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->get('email')" />
                    </span>
                </label>
            </div>

            <div class="card-actions justify-end items-center gap-6 mt-6">
                <input type="submit" value="{{ __('Email Password Reset Link') }}" class="btn btn-secondary max-sm:w-full max-sm:text-xs" />
            </div>
        </form>
    </x-auth-card>

    <a href="{{ route('login') }}" class="link link-hover mt-2">&larr; {{ __('Back to login') }}</a>
</x-guest-layout>