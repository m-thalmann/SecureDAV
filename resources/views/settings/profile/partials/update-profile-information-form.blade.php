<x-detail-section id="update-information">
    <x-slot name="title">
        {{ __('Profile Information') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Update your account\'s profile information and email address.') }}
    </x-slot>

    <x-session-message :message="session('session-message[update-profile-information]')" class="mb-3"></x-session-message>

    <form method="POST" action="{{ route('user-profile-information.update') }}" class="grid grid-cols-6" id="update-profile-information-form">
        @method('PUT')
        @csrf

        <x-form-field name="name" errorBag="updateProfileInformation" class="col-span-6 md:col-span-4">
            <x-slot name="label">{{ __('Name') }}</x-slot>

            <x-input name="name" errorBag="updateProfileInformation" required :value="$user->name" />
        </x-form-field>

        <x-form-field name="email" errorBag="updateProfileInformation" class="col-span-6 md:col-span-4">
            <x-slot name="label">
                <div class="flex gap-4">
                    {{ __('Email') }}

                    @if(config('app.email_verification_enabled'))
                        @if($user->hasVerifiedEmail())
                            <span class="badge badge-success gap-1">
                                <i class="fa-solid fa-circle-check text-xs"></i>
                                {{ __('Verified') }}
                            </span>
                        @else
                            <span class="badge badge-error gap-1">
                                <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                                {{ __('Not verified') }}
                            </span>
                        @endif
                    @endif
                </div>
            </x-slot>

            <x-input name="email" type="email" errorBag="updateProfileInformation" required :value="$user->email" />
        </x-form-field>

        <x-form-field name="timezone" errorBag="updateProfileInformation" class="col-span-6 md:col-span-4">
            <x-slot name="label">{{ __('Timezone') }}</x-slot>

            <select name="timezone" @class([
                'select',
                'input-error' => $errors->getBag('updateProfileInformation')->get('timezone')
            ])>
                <option value="default" @selected($user->timezone === null)>{{ __('Default') }} ({{ config('app.default_timezone') }})</option>

                @foreach ($availableTimezones as $timezone)
                    <option value="{{ $timezone }}" @selected($timezone === $user->timezone)>{{ $timezone }}</option>
                @endforeach
            </select>
        </x-form-field>
    </form>

    <x-slot name="actions" class="mt-6">
        <input type="submit" value="{{ __('Save') }}" form="update-profile-information-form" class="btn btn-neutral" />
    </x-slot>
</x-form-section>