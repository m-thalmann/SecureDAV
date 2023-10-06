@php
    $hasVersion = $file->versions()->exists();

    $showFormInput = !$hasVersion || old('new_file');
@endphp

<x-app-layout :title="__('Create new version')">
    <x-breadcrumbs :file="$file" class="px-4" />

    <div class="card bg-base-200 shadow-lg max-sm:rounded-none md:w-2/3 md:mx-auto">
        <div class="card-body" x-data="{ uploadFile: {{ $showFormInput ? 'true' : 'false' }} }">
            <h2 class="card-title">
                <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                {{ __('Create new version') }}
            </h2>

            <x-session-message :message="session('session-message')" class="my-3"></x-session-message>

            <form action="{{ route('files.file-versions.store', ['file' => $file]) }}" method="post" enctype="multipart/form-data">
                @csrf

                <x-form-field name="label" class="md:w-2/3">
                    <x-slot:label optional>{{ __('Label') }}</x-slot:label>

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
                        <x-slot:label>{{ __('File') }}</x-slot:label>

                        <x-input name="file" type="file" inputClass="file-input" required data-test-id="file-input" />
                    </x-form-field>
                </template>

                <div class="card-actions justify-end">
                    <a href="{{ route('files.show', ['file' => $file->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
                    <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
</x-app-layout>