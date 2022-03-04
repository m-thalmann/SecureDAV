<x-app-layout
    :title="__('Access')"
    :header="[
        'icon' => 'fa-solid fa-shield-alt',
        'items' => [
            [__('Access') => route('access')],
            __('Create access user')
        ]
    ]"
>
    <x-content-card maxWidth="3xl">
        <!-- Validation Errors -->
        <x-form-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('access.add.store') }}">
            @csrf
            <input type="hidden" name="_submit" value="true">

            <!-- Name -->
            <div class="block">
                <x-label for="label" :value="__('Label')" />
                <x-input id="label" class="block mt-1 w-full" type="text" name="label" :value="old('label')" />
            </div>

            <!-- Readonly -->
            <div class="block mt-5">
                <label for="readonly" class="inline-flex items-center">
                    <input
                        id="readonly" type="checkbox"
                        class="rounded border-gray-300 text-orange-500 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200
                                focus:ring-opacity-50 dark:border-none dark:focus:border-none dark:focus:ring-orange-600"
                        name="readonly"
                        @checked(old('_submit', null) === null || old('readonly', 'off') === 'on')
                    >
                    <span class="ml-2 text-gray-600 dark:text-gray-300">{{ __('Read only') }}</span>
                </label>
            </div>

            <!-- Access all -->
            <div class="block mt-5">
                <label for="access_all" class="inline-flex items-center">
                    <input
                        id="access_all" type="checkbox"
                        class="rounded border-gray-300 text-orange-500 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200
                                focus:ring-opacity-50 dark:border-none dark:focus:border-none dark:focus:ring-orange-600"
                        name="access_all"
                        @checked(old('_submit', null) !== null && old('access_all', 'off') === 'on')
                    >
                    <span class="ml-2 text-gray-600 dark:text-gray-300">{{ __('Access all files') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="ml-3">
                    {{ __('Create access user') }}
                </x-button>
            </div>
        </form>
    </x-content-card>
</x-app-layout>