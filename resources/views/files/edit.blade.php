<x-app-layout :title="__('Edit file')">
    <x-breadcrumbs :file="$file" class="px-4" />

    <x-card dialog>
        <x-slot name="title" icon="fas fa-edit">
            {{ __('Edit file') }}
        </x-slot>

        <form action="{{ route('files.update', ['file' => $file->uuid]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="name" class="md:w-2/3">
                <x-slot name="label">{{ __('Name') }}</x-slot>

                <x-input name="name" :value="$file->name" autofocus required />

                <x-slot name="hint">{{ __('Info') }}: {{ __('Including file extension') }}</x-slot>
            </x-form-field>

            <x-form-field name="description" class="md:w-2/3">
                <x-slot name="label" optional>{{ __('Description') }}</x-slot>

                <textarea
                    id="description"
                    name="description"
                    class="textarea leading-snug h-24{{ $errors->get('description') ? ' input-error' : '' }}"
                >{{ $file->description }}</textarea>
            </x-form-field>
        </form>

        <x-slot name="actions">
            <a href="{{ route('files.show', ['file' => $file->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>