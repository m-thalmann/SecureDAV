<x-app-layout :title="__('Create file')">
    <x-breadcrumbs :directories="$directory?->breadcrumbs" class="px-4"/>

    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-file-circle-plus">
            {{ __('Create file') }}
        </x-slot>

        <x-session-message :message="session('session-message')" class="my-3"></x-session-message>

        <form action="{{ route('files.store') }}" method="post" enctype="multipart/form-data" id="create-form">
            @csrf

            @if ($directory)
                <input type="hidden" name="directory_uuid" value="{{ $directory->uuid }}">
            @endif

            <x-form-field name="file" class="md:w-2/3">
                <x-slot name="label">{{ __('File') }}</x-slot>

                <x-input name="file" type="file" inputClass="file-input" required onchange="onSelectedFileChange(this.files[0])" />
            </x-form-field>

            <x-form-field name="name"  class="md:w-2/3">
                <x-slot name="label">{{ __('Name') }}</x-slot>

                <x-input name="name" :value="old('name')" required />

                <x-slot name="hint">{{ __('Info') }}: {{ __('Including file extension') }}</x-slot>
            </x-form-field>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Encrypt file on the server') }}</span> 
                    <input type="checkbox" @checked(old('encrypt')) class="checkbox checkbox-secondary" name="encrypt" />
                </label>
            </div>

            <x-form-field name="description" class="md:w-2/3">
                <x-slot name="label" optional>{{ __('Description') }}</x-slot>

                <textarea
                    id="description"
                    name="description"
                    class="textarea leading-snug h-24{{ $errors->get('description') ? ' input-error' : '' }}"
                >{{ old('description') }}</textarea>
            </x-form-field>
        </form>

        <x-slot name="actions">
            <a href="{{ route('browse.index', ['directory' => $directory?->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Create') }}" form="create-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>