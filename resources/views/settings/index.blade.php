<x-app-layout
    :title="__('Settings')"
    :header="['icon' => 'fa-solid fa-cog', 'items' => [__('Settings')]]"
>
    <x-content-card :title="__('General')" maxWidth="3xl" class="mb-4">
        <!-- Validation Errors -->
        <x-form-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('settings') }}">
            @method('PUT')
            @csrf

            <!-- Name -->
            <div>
                <x-label for="name" :value="__('Name')" />

                <x-input id="name" class="block mt-1 w-full" type="text" :disabled="true" :value="Auth::user()->name" />
            </div>

            <!-- Email Address -->
            <div class="my-4">
                <x-label for="email" :value="__('Email')" />

                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email') ?? Auth::user()->email" required autofocus />
            </div>

            <a class="underline text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-400" href="{{ route('settings.password') }}">
                {{ __('Set a new password') }}
            </a>

            <div class="flex items-center justify-end mt-4">
                <x-button class="ml-3">
                    {{ __('Save changes') }}
                </x-button>
            </div>
        </form>
    </x-content-card>

    <x-content-card :title="__('Danger zone')" maxWidth="3xl">
        <form method="POST" action="{{ route('settings') }}">
            @method('DELETE')
            @csrf

            <x-button :danger="true" onclick="if(!confirm('{{ __('Are you sure you want to delete your account and all files?') }}')) event.preventDefault();">
                {{ __('Delete account') }}
            </x-button>
        </form>
    </x-content-card>
</x-app-layout>