<x-app-layout :title="__('Edit access group')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-user-group">
            {{ __('Edit access group') }}
        </x-slot>

        <form action="{{ route('access-groups.update', [$accessGroup]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label">{{ __('Label') }}</x-slot>

                <x-input name="label" :value="$accessGroup->label" autofocus required />
            </x-form-field>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Read-Only') }}</span>
                    <input type="checkbox" @checked($accessGroup->readonly) class="checkbox checkbox-secondary" name="readonly" />
                </label>
            </div>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Active') }}</span>
                    <input type="checkbox" @checked($accessGroup->active) class="checkbox checkbox-secondary" name="active" />
                </label>
            </div>
        </form>

        <x-slot name="actions">
            <a href="{{ route('access-groups.show', [$accessGroup]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>