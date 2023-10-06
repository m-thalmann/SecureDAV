<x-app-layout :title="__('Update file')">
    <x-breadcrumbs :file="$file" class="px-4">
        <li>
            <i class="fa-solid fa-clock-rotate-left mr-1"></i>
            {{ __('Latest') }}
        </li>
    </x-breadcrumbs>

    <div class="card bg-base-200 shadow-lg max-sm:rounded-none md:w-2/3 md:mx-auto">
        <div class="card-body">
            <h2 class="card-title">
                <i class="fas fa-edit mr-2"></i>
                {{ __('Update file') }}
            </h2>

            <div class="alert bg-base-300 my-3">
                <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                <span>{{ __('This overwrites the current version') }}</span>
            </div>

            <x-session-message :message="session('session-message')" class="my-3"></x-session-message>

            <form action="{{ route('files.file-versions.latest.update', ['file' => $file->uuid]) }}" method="post" enctype="multipart/form-data">
                @method('PUT')
                @csrf

                <x-form-field name="file" class="md:w-2/3">
                    <x-slot:label>{{ __('File') }}</x-slot:label>

                    <x-input name="file" type="file" inputClass="file-input" required />
                </x-form-field>

                <div class="card-actions justify-end">
                    <a href="{{ route('files.show', ['file' => $file]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
                    <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
</x-app-layout>