<x-guest-layout :title="__('Reset password')">
    <x-auth-card>
        <x-session-message :message="session('session-message')" class="mb-3"></x-session-message>

        <form method="POST" action="{{ route('password.update') }}" class="w-full">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <x-form-field name="email" class="w-full">
                <x-slot name="label">{{ __('Email') }}</x-slot>

                <x-input name="email" type="email" :value="old('email', $request->email)" required autocomplete="username" />
            </x-form-field>

            <x-form-field name="password" class="w-full">
                <x-slot name="label">{{ __('Password') }}</x-slot>

                <x-input name="password" type="password" required autofocus autocomplete="new-password" />
            </x-form-field>

            <x-form-field name="password_confirmation" class="w-full">
                <x-slot name="label">{{ __('Confirm Password') }}</x-slot>

                <x-input name="password_confirmation" type="password" required autocomplete="new-password" />
            </x-form-field>

            <div class="card-actions justify-end items-center gap-6 mt-6">
                <input type="submit" value="{{ __('Reset Password') }}" class="btn btn-secondary max-sm:w-full max-sm:text-xs" />
            </div>
        </form>
    </x-auth-card>

    <a href="{{ route('login') }}" class="link link-hover mt-2">&larr; {{ __('Back to login') }}</a>
</x-guest-layout>