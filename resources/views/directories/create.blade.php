<x-app-layout :title="__('Create directory')">
    <x-breadcrumbs :directories="$parentDirectory?->breadcrumbs" class="px-4" />

    <x-card dialog>
        <x-slot:title>
            <i class="fas fa-folder-plus mr-2"></i>
            {{ __('Create directory') }}
        </x-slot:title>

        <form action="{{ route('directories.store') }}" method="post" id="create-form">
            @csrf

            @if ($parentDirectory)
                <input type="hidden" name="parent_directory_uuid" value="{{ $parentDirectory->uuid }}">
            @endif

            <x-form-field name="name" class="md:w-2/3">
                <x-slot:label>{{ __('Name') }}</x-slot:label>

                <x-input name="name" :value="old('name')" autofocus required />
            </x-form-field>
        </form>

        <x-slot:actions>
            <a href="{{ route('browse.index', ['directory' => $parentDirectory?->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Create') }}" form="create-form" class="btn btn-primary">
        </x-slot:actions>
    </x-card>
</x-app-layout>