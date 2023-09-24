<x-app-layout :title="__('Edit directory')">
    <x-breadcrumbs :directories="$directory->breadcrumbs" class="px-4"></x-breadcrumbs>

    <div class="card bg-base-200 shadow-lg max-sm:rounded-none md:w-2/3 md:mx-auto">
        <div class="card-body">
            <h2 class="card-title">
                <i class="fas fa-edit mr-2"></i>
                {{ __('Edit directory') }}
            </h2>

            <form action="{{ route('directories.update', ['directory' => $directory->uuid]) }}" method="post">
                @method('PUT')
                @csrf

                <x-form-field name="name" class="md:w-2/3">
                    <x-slot:label>{{ __('Name') }}</x-slot:label>

                    <x-input name="name" :value="$directory->name" autofocus required />
                </x-form-field>

                <div class="card-actions justify-end">
                    <a href="{{ url()->previous() }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
                    <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
</x-app-layout>