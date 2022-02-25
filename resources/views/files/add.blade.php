<x-app-layout
    :title="__('Files')"
    :header="[
        'icon' => 'fa-solid fa-folder',
        'items' => [
            [__('Files') => route('files')],
            __('Add new file')
        ]
    ]"
>
    <x-content-card maxWidth="3xl">
        <!-- Validation Errors -->
        <x-form-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('files.add.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_submit" value="true">

            <!-- Name -->
            <div class="block" x-data="{ editName: {{ old('name', null) === null ? "false" : "true" }} }">
                <div x-show="editName" x-cloak>
                    <x-label for="name" :value="__('Name')" />

                    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" />
                </div>
                <a x-show="!editName" class="text-gray-700 hover:text-gray-500 cursor-pointer underline dark:text-gray-300" @click="editName = true">{{ __('Change name...') }}</a>
            </div>

            <!-- File -->
            <div class="block mt-4">
                <x-label for="file" :value="__('File')" />

                <x-input id="file" class="inline-block mt-1 bg-stone-200 p-1" type="file" name="file" required />
            </div>

            <!-- Encrypt -->
            <div class="block mt-5">
                <label for="encrypt" class="inline-flex items-center">
                    <input
                        id="encrypt" type="checkbox"
                        class="rounded border-gray-300 text-orange-500 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200
                                focus:ring-opacity-50 dark:border-none dark:focus:border-none dark:focus:ring-orange-600"
                        name="encrypt"
                        @checked(old('_submit', null) === null || old('encrypt', 'off') === 'on')
                    >
                    <span class="ml-2 text-gray-600 dark:text-gray-300">{{ __('Encrypt on server') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="ml-3">
                    {{ __('Upload file') }}
                </x-button>
            </div>
        </form>
    </x-content-card>
</x-app-layout>