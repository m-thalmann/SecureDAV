<x-guest-layout :title="__('Settings')">
    <x-auth-card>
        <x-slot name="title">{{ __('Update password') }}</x-slot>

        <!-- Validation Errors -->
        <x-form-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('settings.password.update') }}">
            @method('PUT')
            @csrf

            <!-- Current Password -->
            <div class="mt-4">
                <x-label for="current_password" :value="__('Current Password')" />

                <x-input id="current_password" class="block mt-1 w-full" type="password" name="current_password" required autofocus />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-label for="password" :value="__('Password')" />

                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-label for="password_confirmation" :value="__('Confirm Password')" />

                <x-input id="password_confirmation" class="block mt-1 w-full"
                                    type="password"
                                    name="password_confirmation" required />
            </div>

            <div class="flex items-center justify-end mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-400" href="{{ route('settings') }}">
                    {{ __('Cancel') }}
                </a>

                <x-button class="ml-5">
                    {{ __('Set new Password') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>