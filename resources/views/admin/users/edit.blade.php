<x-app-layout :title="__('Edit user') . ' - ' . __('Admin area')">
    <x-header-title iconClass="fa-solid fa-screwdriver-wrench">
        <x:slot name="title">
            {{ __('Admin area') }}
        </x:slot>
    </x-header-title>

    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-user-pen">
            {{ __('Edit user') }}
        </x-slot>

        <form action="{{ route('admin.users.update', [$user]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="name" class="md:w-2/3">
                <x-slot name="label">{{ __('Name') }}</x-slot>

                <x-input name="name" :value="$user->name" autofocus required />
            </x-form-field>

            <x-form-field name="email" class="md:w-2/3">
                <x-slot name="label">{{ __('Email') }}</x-slot>

                <x-input name="email" :value="$user->email" required />
            </x-form-field>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <input type="checkbox" @checked($user->is_admin) class="checkbox checkbox-secondary" name="is_admin" />
                    <span class="label-text">{{ __('Admin') }}</span>
                </label>
            </div>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <input type="checkbox" class="checkbox checkbox-secondary" name="reset_password" />
                    <span class="label-text flex items-center gap-2">
                        {{ __('Reset password') }}
                        <i class="fa-solid fa-rotate-right"></i>
                    </span>
                </label>
            </div>
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('admin.users.index')) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>
