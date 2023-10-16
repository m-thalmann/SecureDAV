<x-guest-layout :title="__('Confirm password')">
    <x-auth-card>
        <div class="text-left text-sm">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="w-full">
            @csrf

            <x-form-field name="password" class="w-full">
                <x-slot name="label">{{ __('Password') }}</x-slot>

                <x-input name="password" type="password" required autocomplete="current-password" autofocus />
            </x-form-field>

            <div class="card-actions justify-end mt-6">
                <input type="submit" value="{{ __('Confirm') }}" class="btn btn-secondary" />
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>