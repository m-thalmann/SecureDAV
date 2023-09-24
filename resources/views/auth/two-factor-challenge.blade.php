<x-guest-layout :title="__('Confirm password')">
    <x-auth-card>
        <div class="text-left text-sm">
            {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
        </div>

        <form method="POST" action="{{ route('two-factor.login') }}" class="w-full" x-data="{ recovery: {{ !empty($errors->get('recovery_code')) ? 'true' : 'false' }} }">
            @csrf

            <x-form-field name="code" class="w-full" x-show="!recovery">
                <x-slot:label>{{ __('Code') }}</x-slot:label>

                <x-input name="code" inputmode="numeric" autofocus autocomplete="one-time-code" x-ref="code" />
            </x-form-field>

            <x-form-field name="recovery_code" class="w-full" x-show="recovery" x-cloak>
                <x-slot:label>{{ __('Recovery Code') }}</x-slot:label>

                <x-input name="recovery_code" autocomplete="one-time-code" x-ref="recovery_code" />
            </x-form-field>

            <div class="card-actions justify-end items-center gap-6 mt-6">
                <a
                    class="link"
                    x-on:click="
                        recovery = !recovery;
                        $nextTick(() => { (recovery ? $refs.recovery_code : $refs.code).focus(); })
                    "
                >
                    <span x-show="!recovery">{{ __('Use a recovery code') }}</span>
                    <span x-show="recovery" x-cloak>{{ __('Use an authentication code') }}</span>
                </a>

                <input type="submit" value="{{ __('Log in') }}" class="btn btn-secondary" />
            </div>
        </form>
    </x-auth-card>

    <a href="{{ route('login') }}" class="link link-hover mt-2">&larr; {{ __('Back to login') }}</a>
</x-guest-layout>