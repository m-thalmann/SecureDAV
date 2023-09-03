<x-form-section submit="updateProfileInformation">
    <x-slot name="title">
        {{ __('Profile Information') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Update your account\'s profile information and email address.') }}
    </x-slot>

    <x-slot name="form">
        <x-session-message :message="session('session-message[update-profile-information]')" class="mb-3"></x-session-message>

        <form method="POST" action="{{ route('user-profile-information.update') }}" class="grid grid-cols-6">
            @method('PUT')
            @csrf

            <div class="form-control col-span-6 md:col-span-4">
                <label class="label" for="name">
                    <span class="label-text">{{ __('Name') }}</span>
                </label>
                <input id="name" type="text" name="name" class="input input-md w-full{{ $errors->updateProfileInformation->get('name') ? ' input-error' : '' }}" value="{{ $user->name }}" required />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->updateProfileInformation->get('name')" />
                    </span>
                </label>
            </div>

            <div class="form-control col-span-6 md:col-span-4">
                <label class="label" for="email">
                    <span class="label-text">{{ __('Email') }}</span>
                </label>
                <input id="email" type="email" name="email" class="input input-md w-full{{ $errors->updateProfileInformation->get('email') ? ' input-error' : '' }}" value="{{ $user->email }}" required />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->updateProfileInformation->get('email')" />
                    </span>
                </label>
            </div>

            <!-- TODO: Email verification -->

            <div class="card-actions col-span-6 justify-end mt-6">
                <input type="submit" value="{{ __('Save') }}" class="btn btn-neutral" />
            </div>
        </form>
    </x-slot>
</x-form-section>