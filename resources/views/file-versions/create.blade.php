@php
    $showFormInput = !$hasVersion || old('new_file');
@endphp

<x-app-layout :title="__('Create new version')">
    <x-breadcrumbs :file="$file" class="px-4" />

    <x-card dialog x-data="{ uploadFile: {{ $showFormInput ? 'true' : 'false' }} }">
        <x-slot name="title" icon="fa-solid fa-clock-rotate-left">
            {{ __('Create new version') }}
        </x-slot>

        <x-session-message :message="session('session-message')" class="my-3"></x-session-message>

        <form action="{{ route('files.versions.store', ['file' => $file]) }}" method="post" enctype="multipart/form-data" id="create-form">
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label" optional>{{ __('Label') }}</x-slot>

                <x-input name="label" :value="old('label')" autofocus />
            </x-form-field>

            @if ($hasVersion)
                <div class="form-control w-fit">
                    <label class="label cursor-pointer gap-4">
                        <span class="label-text">{{ __('Upload new file') }}</span> 
                        <input type="checkbox" class="checkbox checkbox-secondary" name="new_file" x-model="uploadFile" />
                    </label>
                </div>
            @endif

            <template x-if="uploadFile" data-file-input-is-shown="{{ $showFormInput ? 'true' : 'false'  }}">
                <x-form-field name="file" class="md:w-2/3">
                    <x-slot name="label">{{ __('File') }}</x-slot>

                    <x-input name="file" type="file" inputClass="file-input" required data-test-id="file-input" />
                </x-form-field>
            </template>
        </form>

        <x-slot name="actions">
            <a href="{{ route('files.show', ['file' => $file->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="create-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>