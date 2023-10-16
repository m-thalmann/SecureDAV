<x-app-layout :title="__('Edit file')">
    <x-breadcrumbs :file="$file" class="px-4" />

    <x-card dialog>
        <x-slot:title>
            <i class="fas fa-edit mr-2"></i>
            {{ __('Edit file') }}
        </x-slot:title>

        <form action="{{ route('files.update', ['file' => $file->uuid]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="name" class="md:w-2/3">
                <x-slot:label>{{ __('Name') }}</x-slot:label>

                <div class="relative">
                    <x-input name="name" class="{{ $file->extension ? 'pr-16' : '' }}" :value="$file->name" autofocus required />

                    @if ($file->extension)
                        <span class="absolute top-0 bottom-0 right-0 px-4 flex items-center bg-base-200/50 text-base-content/70 rounded-lg">.{{ $file->extension }}</span>
                    @endif
                </div>

                <x-slot:hint>{{ __('Info') }}: {{ __('Without file extension') }}</x-slot:hint>
            </x-form-field>

            <x-form-field name="description" class="md:w-2/3">
                <x-slot:label optional>{{ __('Description') }}</x-slot:label>

                <textarea
                    id="description"
                    name="description"
                    class="textarea leading-snug h-24{{ $errors->get('description') ? ' input-error' : '' }}"
                >{{ $file->description }}</textarea>
            </x-form-field>
        </form>

        <x-slot:actions>
            <a href="{{ route('files.show', ['file' => $file->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot:actions>
    </x-card>
</x-app-layout>