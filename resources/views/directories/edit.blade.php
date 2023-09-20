<x-app-layout :title="__('Edit directory')">
    <div class="card bg-base-200 shadow-lg max-sm:rounded-none md:w-2/3 md:mx-auto">
        <div class="card-body">
            <h2 class="card-title">
                <i class="fas fa-edit mr-2"></i>
                {{ __('Edit directory') }}
            </h2>
            <h3 class="breadcrumbs text-sm text-base-content/80">
                <ul>
                    <li class="h-6"><i class="fas fa-home"></i></li>

                    @foreach ($directory->breadcrumbs as $breadcrumb)
                        <li>{{ $breadcrumb->name }}</li>
                    @endforeach
                </ul>
            </h3>

            <form action="{{ route('directories.update', ['directory' => $directory->uuid]) }}" method="post">
                @method('PUT')
                @csrf

                <div class="form-control md:w-2/3">
                    <label class="label" for="name">
                        <span class="label-text">{{ __('Name') }}</span>
                    </label>
                    <input id="name" type="text" name="name" class="input input-md w-full{{ $errors->get('name') ? ' input-error' : '' }}" value="{{ $directory->name }}" autofocus required />
                    <label class="label">
                        <span class="label-text-alt">
                            <x-input-error :messages="$errors->get('name')" />
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