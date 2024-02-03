<x-app-layout :title="__('Create file')">
    <x-breadcrumbs :directories="$directory?->breadcrumbs" class="px-4"/>

    <x-card dialog x-data="{ uploadFile: {{ Js::from(old('initialize', 'true') === 'true') }}, selectedFile: null }">
        <x-slot name="title" icon="fa-solid fa-file-circle-plus">
            {{ __('Create file') }}
        </x-slot>

        <x-session-message :message="session('session-message')" class="my-3"></x-session-message>

        <form action="{{ route('files.store') }}" method="post" enctype="multipart/form-data" id="create-form" class="pt-4">
            @csrf

            @if ($directory)
                <input type="hidden" name="directory_uuid" value="{{ $directory->uuid }}">
            @endif

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Initialize with file') }}</span>
                    <input type="checkbox" class="checkbox checkbox-secondary" x-model="uploadFile" />
                </label>
            </div>

            <input type="hidden" name="initialize" x-bind:value="uploadFile">

            <x-form-field name="file" class="md:w-2/3" x-show="uploadFile" x-cloak>
                <x-slot name="label">{{ __('File') }}</x-slot>

                <x-input
                    name="file"
                    type="file"
                    inputClass="file-input"
                    required
                    x-bind:disabled="!uploadFile"
                    x-init="selectedFile = $el.files[0]"
                    x-on:change="selectedFile = $el.files[0]"
                />
            </x-form-field>

            <x-form-field name="name"  class="md:w-2/3">
                <x-slot name="label">{{ __('Name') }}</x-slot>

                <x-input name="name" x-bind:value="selectedFile?.name ?? {{ Js::from(old('name')) }}" required x-bind:disabled="uploadFile && !selectedFile" />

                <x-slot name="hint">{{ __('Info') }}: {{ __('Including file extension') }}</x-slot>
            </x-form-field>

            <template x-if="uploadFile">
                <div class="form-control w-fit">
                    <label class="label cursor-pointer gap-4">
                        <span class="label-text">{{ __('Encrypt file on the server') }}</span>
                        <input type="checkbox" @checked(old('encrypt')) class="checkbox checkbox-secondary" name="encrypt" />
                    </label>
                </div>
            </template>

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
            <a href="{{ previousUrl(fallback: route('browse.index', [$directory])) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Create') }}" form="create-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>