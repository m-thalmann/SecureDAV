<x-app-layout :title="__('Edit access user')">
    <x-card dialog>
        <x-slot name="title">
            <i class="fas fa-user-plus mr-2"></i>

            <div>
                {{ __('Edit access user') }}

                <small class="block text-sm font-normal text-base-content/60">{{ $accessUser->username }}</small>
            </div>
        </x-slot>

        <form action="{{ route('access-users.update', ['access_user' => $accessUser->username]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label" optional>{{ __('Label') }}</x-slot>

                <x-input name="label" :value="$accessUser->label" autofocus />
            </x-form-field>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Read-Only') }}</span>
                    <input type="checkbox" @checked($accessUser->readonly) class="checkbox checkbox-secondary" name="readonly" />
                </label>
            </div>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Active') }}</span>
                    <input type="checkbox" @checked($accessUser->active) class="checkbox checkbox-secondary" name="active" />
                </label>
            </div>
        </form>

        <x-slot name="actions">
            <a href="{{ route('access-users.show', ['access_user' => $accessUser->username]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>