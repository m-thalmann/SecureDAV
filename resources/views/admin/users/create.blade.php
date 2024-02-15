<x-app-layout :title="__('Create user') . ' - ' . __('Admin area')">
    <x-header-title iconClass="fa-solid fa-screwdriver-wrench">
        <x:slot name="title">
            {{ __('Admin area') }}
        </x:slot>
    </x-header-title>

    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-user-plus">
            {{ __('Create user') }}
        </x-slot>

        <form action="{{ route('admin.users.store') }}" method="post" id="create-form">
            @csrf

            <x-form-field name="name" class="md:w-2/3">
                <x-slot name="label">{{ __('Name') }}</x-slot>

                <x-input name="name" :value="old('name')" autofocus required />
            </x-form-field>

            <x-form-field name="email" class="md:w-2/3">
                <x-slot name="label">{{ __('Email') }}</x-slot>

                <x-input name="email" :value="old('email')" required />
            </x-form-field>

            <x-form-field name="password" class="md:w-2/3">
                <x-slot name="label">{{ __('Password') }}</x-slot>

                <x-input name="password" type="password" required autocomplete="new-password" />
            </x-form-field>

            <x-form-field name="password_confirmation" class="md:w-2/3">
                <x-slot name="label">{{ __('Confirm Password') }}</x-slot>

                <x-input name="password_confirmation" type="password" required autocomplete="new-password" />
            </x-form-field>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <input type="checkbox" @checked(old('is_admin')) class="checkbox checkbox-secondary" name="is_admin" />
                    <span class="label-text">{{ __('Admin') }}</span>
                </label>
            </div>
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('admin.users.index')) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Create') }}" form="create-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>
