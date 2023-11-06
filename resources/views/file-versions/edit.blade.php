<x-app-layout :title="__('Edit version')">
    <x-breadcrumbs :file="$file" class="px-4">
        <li>
            <i class="fa-solid fa-clock-rotate-left mr-1"></i>
            {{ $fileVersion->version }}
        </li>
    </x-breadcrumbs>

    <x-card dialog>
        <x-slot name="title" icon="fas fa-edit">
            {{ __('Edit version') }}
        </x-slot>

        <form
            action="{{ route('files.versions.update', [$file, $fileVersion]) }}"
            method="post"
            id="edit-form"
        >
            @method('PUT')
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label" optional>{{ __('Label') }}</x-slot>

                <x-input name="label" :value="$fileVersion->label" autofocus />
            </x-form-field>
        </form>

        <x-slot name="actions">
            <a href="{{ route('files.show', [$file]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>