<x-app-layout :title="__('Edit directory')">
    <x-breadcrumbs :directories="$directory->breadcrumbs" class="px-4" />

    <x-card dialog>
        <x-slot:title>
            <i class="fas fa-edit mr-2"></i>
            {{ __('Edit directory') }}
        </x-slot:title>

        <form action="{{ route('directories.update', ['directory' => $directory->uuid]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="name" class="md:w-2/3">
                <x-slot:label>{{ __('Name') }}</x-slot:label>

                <x-input name="name" :value="$directory->name" autofocus required />
            </x-form-field>
        </form>

        <x-slot:actions>
            <a href="{{ route('browse.index', ['directory' => $directory->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot:actions>
    </x-card>
</x-app-layout>