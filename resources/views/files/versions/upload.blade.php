<x-app-layout
    :title="__('Files')"
    :header="[
        'icon' => 'fa-solid fa-upload',
        'items' => [
            [__('Files') => route('files')],
            [$file->display_name => route('files.details', ['file' => $file->uuid])],
            __('Upload file')
        ]
    ]"
>
    <x-content-card maxWidth="3xl">
        <!-- Validation Errors -->
        <x-form-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('files.versions.upload', ['file' => $file->uuid]) }}" enctype="multipart/form-data">
            @method('PUT')
            @csrf

            <input type="hidden" name="_submit" value="true">

            <!-- File -->
            <div class="block">
                <x-label for="file" :value="__('File')" />

                <x-input id="file" class="inline-block mt-1 bg-stone-200 p-1" type="file" name="file" required />
            </div>

            <!-- New version -->
            <div class="block mt-5">
                <label for="new_version" class="inline-flex items-center">
                    <input
                        id="new_version" type="checkbox"
                        class="rounded border-gray-300 text-orange-500 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200
                                focus:ring-opacity-50 dark:border-none dark:focus:border-none dark:focus:ring-orange-600"
                        name="new_version"
                        @checked(old('_submit', null) !== null && old('new_version', 'off') === 'on')
                    >
                    <span class="ml-2 text-gray-600 dark:text-gray-300">{{ __('Create new version') }}</span>
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