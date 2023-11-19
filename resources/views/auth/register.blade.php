<x-guest-layout :title="__('Registration')">
    <x-auth-card :subtitle="__('Registration')">
        <x-session-message :message="session('session-message')" class="mb-3"></x-session-message>

        <form method="POST" action="{{ route('register') }}" class="w-full">
            @csrf

            <x-form-field name="name" class="w-full">
                <x-slot name="label">{{ __('Name') }}</x-slot>

                <x-input name="name" :value="old('name')" required autofocus />
            </x-form-field>

            <x-form-field name="email" class="w-full">
                <x-slot name="label">{{ __('Email') }}</x-slot>

                <x-input name="email" type="email" :value="old('email')" required autocomplete="username" />
            </x-form-field>

            <x-form-field name="password" class="w-full">
                <x-slot name="label">{{ __('Password') }}</x-slot>

                <x-input name="password" type="password" required autocomplete="new-password" />
            </x-form-field>

            <x-form-field name="password_confirmation" class="w-full">
                <x-slot name="label">{{ __('Confirm Password') }}</x-slot>

                <x-input name="password_confirmation" type="password" required autocomplete="new-password" />
            </x-form-field>

            <div class="card-actions justify-end mt-6">
                <input type="submit" value="{{ __('Register') }}" class="btn btn-secondary" />
            </div>
        </form>
    </x-auth-card>

    <a class="link mt-4" href="{{ route('login') }}">{{ __('Already have an account?') }}</a>
</x-guest-layout>