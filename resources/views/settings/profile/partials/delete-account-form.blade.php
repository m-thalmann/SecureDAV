<x-form-section id="delete-account">
    <x-slot name="title">
        {{ __('Delete Account') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Permanently delete your account.') }}
    </x-slot>

    <x-slot name="form">
        <x-session-message :message="session('session-message[delete-account]')" class="mb-3"></x-session-message>

        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}

        <form method="POST" action="{{ route('settings.profile.destroy') }}" onsubmit="return confirm('{{ __('Are you sure you want to permanently delete your account?') }}')">
            @method('DELETE')
            @csrf

            <div class="card-actions mt-6">
                <button class="btn btn-error">{{ __('Delete Account') }}</button>
            </div>
        </form>
    </x-slot>
</x-form-section>