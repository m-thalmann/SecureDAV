<x-app-layout :title="__('Edit group user')">
    <x-card dialog>
        <x-slot name="title">
            <i class="fa-solid fa-user-pen mr-2"></i>

            <div>
                {{ __('Edit group user') }}
                <small class="block text-sm font-normal text-base-content/60">
                    <i class="fa-solid fa-user-group mr-1"></i>
                    {{ $accessGroupUser->accessGroup->label }}
                </small>
            </div>
        </x-slot>

        <form action="{{ route('access-group-users.update', ['access_group_user' => $accessGroupUser->username]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label">{{ __('Label') }}</x-slot>

                <x-input name="label" :value="$accessGroupUser->label" autofocus required />
            </x-form-field>
        </form>

        <x-slot name="actions">
            <a href="{{ route('access-groups.show', ['access_group' => $accessGroupUser->accessGroup->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>