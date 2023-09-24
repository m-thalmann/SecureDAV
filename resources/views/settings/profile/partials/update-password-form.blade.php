<x-form-section id="update-password">
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

            <x-form-field name="current_password" errorBag="updatePassword" class="col-span-6 md:col-span-4">
                <x-slot:label>{{ __('Current Password') }}</x-slot:label>

                <x-input name="current_password" type="password" errorBag="updatePassword" required autocomplete="current-password" />
            </x-form-field>

            <x-form-field name="password" errorBag="updatePassword" class="col-span-6 md:col-span-4">
                <x-slot:label>{{ __('New Password') }}</x-slot:label>

                <x-input name="password" type="password" errorBag="updatePassword" required autocomplete="new-password" />
            </x-form-field>

            <x-form-field name="password_confirmation" errorBag="updatePassword" class="col-span-6 md:col-span-4">
                <x-slot:label>{{ __('Confirm Password') }}</x-slot:label>

                <x-input name="password_confirmation" type="password" errorBag="updatePassword" required autocomplete="new-password" />
            </x-form-field>

            <div class="card-actions col-span-6 justify-end mt-6">
                <input type="submit" value="{{ __('Save') }}" class="btn btn-neutral" />
            </div>
        </form>
    </x-slot>
</x-form-section>
