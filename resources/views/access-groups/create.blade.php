<x-app-layout :title="__('Create access group')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-user-group">
            {{ __('Create access group') }}
        </x-slot>

        <form action="{{ route('access-groups.store') }}" method="post" id="create-form">
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label">{{ __('Label') }}</x-slot>

                <x-input name="label" :value="old('label')" autofocus required />
            </x-form-field>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Read-Only') }}</span>
                    <input type="checkbox" @checked(old('readonly', true)) class="checkbox checkbox-secondary" name="readonly" />
                </label>
            </div>
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('access-groups.index')) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Create') }}" form="create-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>