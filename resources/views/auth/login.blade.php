<x-guest-layout :title="__('Login')">
    <x-auth-card>
        <x-session-message :message="session('session-message')" class="mb-3"></x-session-message>

        <form method="POST" action="{{ route('login') }}" class="w-full">
            @csrf

            <x-form-field name="email" class="w-full">
                <x-slot:label>{{ __('Email') }}</x-slot:label>

                <x-input name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />
            </x-form-field>

            <x-form-field name="password" class="w-full">
                <x-slot:label>{{ __('Password') }}</x-slot:label>

                <x-input name="password" type="password" required autocomplete="current-password" />
            </x-form-field>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <input type="checkbox" @checked(old('remember')) class="checkbox checkbox-secondary" name="remember" />
                    <span class="label-text">{{ __('Remember me') }}</span> 
                </label>
            </div>

            <div class="card-actions justify-end items-center gap-6 mt-6">
                <a class="link" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>

                <input type="submit" value="{{ __('Log in') }}" class="btn btn-secondary" />
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>