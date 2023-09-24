<x-app-layout :title="__('Create directory')">
    <x-breadcrumbs :directories="$parentDirectory?->breadcrumbs" class="px-4"></x-breadcrumbs>

    <div class="card bg-base-200 shadow-lg max-sm:rounded-none md:w-2/3 md:mx-auto">
        <div class="card-body">
            <h2 class="card-title">
                <i class="fas fa-folder-plus mr-2"></i>
                {{ __('Create directory') }}
            </h2>

            <form action="{{ route('directories.store') }}" method="post">
                @csrf

                @if ($parentDirectory)
                    <input type="hidden" name="parent_directory_uuid" value="{{ $parentDirectory->uuid }}">
                @endif

                <x-form-field name="name" class="md:w-2/3">
                    <x-slot:label>{{ __('Name') }}</x-slot:label>

                    <x-input name="name" :value="old('name')" autofocus required />
                </x-form-field>

                <div class="card-actions justify-end">
                    <a href="{{ url()->previous() }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
                    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
</x-app-layout>