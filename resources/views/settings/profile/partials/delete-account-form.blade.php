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

        <form method="POST" action="{{ route('settings.profile.destroy') }}">
            @method('DELETE')
            @csrf

            <div class="card-actions mt-6">
                <input
                    type="submit"
                    value="{{ __('Delete Account') }}"
                    class="btn btn-error"
                    onclick="if(!confirm('{{ __('Are you sure you want to permanently delete your account?') }}')) event.preventDefault();"
                />
            </div>
        </form>
    </x-slot>
</x-form-section>