<x-app-layout :title="__('Edit directory')">
    <x-breadcrumbs :directories="$directory->breadcrumbs" class="px-4" />

    <x-card dialog>
        <x-slot name="title" icon="fas fa-edit">
            {{ __('Edit directory') }}
        </x-slot>

        <form action="{{ route('directories.update', [$directory]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="name" class="md:w-2/3">
                <x-slot name="label">{{ __('Name') }}</x-slot>

                <x-input name="name" :value="$directory->name" autofocus required />
            </x-form-field>
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('browse.index', [$directory])) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>