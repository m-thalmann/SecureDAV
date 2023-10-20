<x-app-layout :title="__('Create group user')">
    <x-card dialog>
        <x-slot name="title">
            <i class="fa-solid fa-user-plus mr-2"></i>

            <div>
                <span>{{ __('Create group user') }}</span>
                <small class="block text-sm font-normal text-base-content/60">{{ $accessGroup->label }}</small>
            </div>
        </x-slot>

        <div class="alert bg-base-300 my-3">
            <i class="fa-solid fa-circle-info text-info"></i>
            <span>{{ __('The username and password will be generated automatically') }}</span>
        </div>

        <form action="{{ route('access-groups.access-group-users.store', ['access_group' => $accessGroup->uuid]) }}" method="post" id="create-form">
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label">{{ __('Label') }}</x-slot>

                <x-input name="label" :value="old('label')" autofocus required />
            </x-form-field>
        </form>

        <x-slot name="actions">
            <a href="{{ route('access-groups.show', ['access_group' => $accessGroup->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Create') }}" form="create-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>