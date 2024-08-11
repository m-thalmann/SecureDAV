<x-app-layout :title="__('Create WebDav User')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-user-plus">
            {{ __('Create WebDav User') }}
        </x-slot>

        <div class="alert bg-base-300 my-3">
            <i class="fa-solid fa-circle-info text-info"></i>
            <span>{{ __('The username and password will be generated automatically') }}</span>
        </div>

        <form action="{{ route('web-dav-users.store') }}" method="post" id="create-form">
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label">{{ __('Label') }}</x-slot>

                <x-input name="label" :value="old('label')" autofocus />
            </x-form-field>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Read-Only') }}</span>
                    <input type="checkbox" @checked(old('readonly', true)) class="checkbox checkbox-secondary" name="readonly" />
                </label>
            </div>
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('web-dav-users.index')) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Create') }}" form="create-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>
