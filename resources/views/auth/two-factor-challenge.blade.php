<x-guest-layout :title="__('Confirm password')">
    <x-auth-card>
        <div class="text-left text-sm">
            {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
        </div>

        <form method="POST" action="{{ route('two-factor.login') }}" class="w-full" x-data="{ recovery: {{ !empty($errors->get('recovery_code')) ? 'true' : 'false' }} }">
            @csrf

            <div class="form-control w-full" x-show="!recovery">
                <label class="label" for="code">
                    <span class="label-text">{{ __('Code') }}</span>
                </label>
                <input id="code" type="text" inputmode="numeric" name="code" x-ref="code" class="input input-md w-full{{ $errors->get('code') ? ' input-error' : '' }}" autofocus autocomplete="one-time-code" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->get('code')" />
                    </span>
                </label>
            </div>

            <div class="form-control w-full" x-show="recovery" x-cloak>
                <label class="label" for="recovery_code">
                    <span class="label-text">{{ __('Recovery Code') }}</span>
                </label>
                <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" class="input input-md w-full{{ $errors->get('recovery_code') ? ' input-error' : '' }}" autocomplete="one-time-code" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->get('recovery_code')" />
                    </span>
                </label>
            </div>

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