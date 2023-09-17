<x-guest-layout :title="__('Confirm password')">
    <x-auth-card>
        <div class="text-left text-sm">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="w-full">
            @csrf

            <div class="form-control w-full">
                <label class="label" for="password">
                    <span class="label-text">{{ __('Password') }}</span>
                </label>
                <input id="password" type="password" name="password" class="input input-md w-full{{ $errors->get('password') ? ' input-error' : '' }}" required autocomplete="current-password" autofocus />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->get('password')" />
                    </span>
                </label>
            </div>

            <div class="card-actions justify-end mt-6">
                <input type="submit" value="{{ __('Confirm') }}" class="btn btn-secondary" />
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>