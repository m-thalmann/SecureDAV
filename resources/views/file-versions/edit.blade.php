<x-app-layout :title="__('Edit version')">
    <x-breadcrumbs :file="$fileVersion->file" class="px-4">
        <li>
            <i class="fa-solid fa-clock-rotate-left mr-1"></i>
            {{ $fileVersion->version }}
        </li>
    </x-breadcrumbs>

    <div class="card bg-base-200 shadow-lg max-sm:rounded-none md:w-2/3 md:mx-auto">
        <div class="card-body">
            <h2 class="card-title">
                <i class="fas fa-edit mr-2"></i>
                {{ __('Edit version') }}
            </h2>

            <form action="{{ route('file-versions.update', ['file_version' => $fileVersion->id]) }}" method="post">
                @method('PUT')
                @csrf

                <x-form-field name="label" class="md:w-2/3">
                    <x-slot:label optional>{{ __('Label') }}</x-slot:label>

                    <x-input name="label" :value="$fileVersion->label" autofocus />
                </x-form-field>

                <div class="card-actions justify-end">
                    <a href="{{ url()->previous() }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
                    <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
</x-app-layout>