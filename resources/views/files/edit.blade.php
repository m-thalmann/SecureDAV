<x-app-layout :title="__('Edit file')">
    <x-breadcrumbs :directories="$file->directory?->breadcrumbs" class="px-4">
        <li class="flex items-center gap-2"><i class="fas fa-file"></i> {{ $file->fileName }}</li>
    </x-breadcrumbs>

    <div class="card bg-base-200 shadow-lg max-sm:rounded-none md:w-2/3 md:mx-auto">
        <div class="card-body">
            <h2 class="card-title">
                <i class="fas fa-edit mr-2"></i>
                {{ __('Edit file') }}
            </h2>

            <form action="{{ route('files.update', ['file' => $file->uuid]) }}" method="post">
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

                <div class="card-actions justify-end">
                    <a href="{{ url()->previous() }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
                    <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
</x-app-layout>