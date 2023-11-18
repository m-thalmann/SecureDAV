<x-app-layout :title="__('Create directory')">
    <x-breadcrumbs :directories="$parentDirectory?->breadcrumbs" class="px-4" />

    <x-card dialog>
        <x-slot name="title" icon="fas fa-folder-plus">
            {{ __('Create directory') }}
        </x-slot>

        <form action="{{ route('directories.store') }}" method="post" id="create-form">
            @csrf

            @if ($parentDirectory)
                <input type="hidden" name="parent_directory_uuid" value="{{ $parentDirectory->uuid }}">
            @endif

            <x-form-field name="name" class="md:w-2/3">
                <x-slot name="label">{{ __('Name') }}</x-slot>

                <x-input name="name" :value="old('name')" autofocus required />
            </x-form-field>
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('browse.index', [$parentDirectory])) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Create') }}" form="create-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>