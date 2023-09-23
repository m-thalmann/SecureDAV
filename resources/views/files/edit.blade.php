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

                <div class="form-control md:w-2/3">
                    <label class="label" for="name">
                        <span class="label-text">{{ __('Name') }}</span>
                    </label>
                    <input id="name" type="text" name="name" class="input input-md w-full{{ $errors->get('name') ? ' input-error' : '' }}" value="{{ $file->name }}" autofocus required />
                    <label class="label">
                        <span class="label-text-alt">
                            <x-input-error :messages="$errors->get('name')" />
                        </span>
                    </label>
                </div>

                <div class="form-control md:w-2/3">
                    <label class="label" for="description">
                        <span class="label-text">{{ __('Description') }}</span>
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        class="textarea leading-snug h-24{{ $errors->get('description') ? ' input-error' : '' }}"
                        >{{ $file->description }}</textarea>
                    <label class="label">
                        <span class="label-text-alt">
                            <x-input-error :messages="$errors->get('description')" />
                        </span>
                    </label>
                </div>

                <div class="card-actions justify-end">
                    <a href="{{ url()->previous() }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
                    <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
</x-app-layout>