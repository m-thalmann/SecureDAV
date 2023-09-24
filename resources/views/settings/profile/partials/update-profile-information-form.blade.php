<x-form-section id="update-information">
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

            <x-form-field name="name" errorBag="updateProfileInformation" class="col-span-6 md:col-span-4">
                <x-slot:label>{{ __('Name') }}</x-slot:label>

                <x-input name="name" errorBag="updateProfileInformation" required :value="$user->name" />
            </x-form-field>

            <x-form-field name="email" errorBag="updateProfileInformation" class="col-span-6 md:col-span-4">
                <x-slot:label>
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
                </x-slot:label>

                <x-input name="email" type="email" errorBag="updateProfileInformation" required :value="$user->email" />
            </x-form-field>

            <div class="card-actions col-span-6 justify-end mt-6">
                <input type="submit" value="{{ __('Save') }}" class="btn btn-neutral" />
            </div>
        </form>
    </x-slot>
</x-form-section>