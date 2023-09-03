<x-form-section>
    <x-slot name="title">
        {{ __('Update Password') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Ensure your account is using a long, random password to stay secure.') }}
    </x-slot>

    <x-slot name="form">
        <x-session-message :message="session('session-message[update-password]')" class="mb-3"></x-session-message>

        <form method="POST" action="{{ route('user-password.update') }}" class="grid grid-cols-6">
            @method('PUT')
            @csrf

            <div class="form-control col-span-6 md:col-span-4">
                <label class="label" for="current_password">
                    <span class="label-text">{{ __('Current Password') }}</span>
                </label>
                <input id="current_password" type="password" name="current_password" class="input input-md w-full{{ $errors->updatePassword->get('current_password') ? ' input-error' : '' }}" required autocomplete="current-password" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" />
                    </span>
                </label>
            </div>

            <div class="form-control col-span-6 md:col-span-4">
                <label class="label" for="password">
                    <span class="label-text">{{ __('New Password') }}</span>
                </label>
                <input id="password" type="password" name="password" class="input input-md w-full{{ $errors->updatePassword->get('password') ? ' input-error' : '' }}" required autocomplete="new-password" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->updatePassword->get('password')" />
                    </span>
                </label>
            </div>

            <div class="form-control col-span-6 md:col-span-4">
                <label class="label" for="password_confirmation">
                    <span class="label-text">{{ __('Confirm Password') }}</span>
                </label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="input input-md w-full{{ $errors->updatePassword->get('password_confirmation') ? ' input-error' : '' }}" required autocomplete="new-password" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
                    </span>
                </label>
            </div>

            <div class="card-actions col-span-6 justify-end mt-6">
                <input type="submit" value="{{ __('Save') }}" class="btn btn-neutral" />
            </div>
        </form>
    </x-slot>
</x-form-section>
