<x-app-layout :title="__('Update file')">
    <x-breadcrumbs :file="$file" class="px-4">
        <li>
            <i class="fa-solid fa-clock-rotate-left mr-1"></i>
            {{ __('Latest') }}
        </li>
    </x-breadcrumbs>

    <x-card dialog>
        <x-slot name="title" icon="fas fa-edit">
            {{ __('Update file') }}
        </x-slot>

        <div class="alert bg-base-300 my-3">
            <i class="fa-solid fa-triangle-exclamation text-warning"></i>
            <span>{{ __('This overwrites the current version') }}</span>
        </div>

        <x-session-message :message="session('session-message')" class="my-3"></x-session-message>

        <form
            action="{{ route('files.versions.latest.update', [$file]) }}"
            method="post"
            enctype="multipart/form-data"
            id="edit-form"
        >
            @method('PUT')
            @csrf

            <x-form-field name="file" class="md:w-2/3">
                <x-slot name="label">{{ __('File') }}</x-slot>

                <x-input name="file" type="file" inputClass="file-input" required />
            </x-form-field>
        </form>

        <x-slot name="actions">
            <a href="{{ route('files.show', [$file]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>