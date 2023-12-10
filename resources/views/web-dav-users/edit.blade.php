<x-app-layout :title="__('Edit WebDav User')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-user-pen">
            {{ __('Edit WebDav User') }}
        </x-slot>

        <form action="{{ route('web-dav-users.update', [$webDavUser]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label">{{ __('Label') }}</x-slot>

                <x-input name="label" :value="$webDavUser->label" autofocus required />
            </x-form-field>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Read-Only') }}</span>
                    <input type="checkbox" @checked($webDavUser->readonly) class="checkbox checkbox-secondary" name="readonly" />
                </label>
            </div>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Active') }}</span>
                    <input type="checkbox" @checked($webDavUser->active) class="checkbox checkbox-secondary" name="active" />
                </label>
            </div>
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('web-dav-users.show', [$webDavUser])) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>