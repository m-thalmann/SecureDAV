<x-app-layout :title="__('Create Backup Configuration')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-plus">
            {{ __('Create Backup Configuration') }}
        </x-slot>

        <x-slot name="subtitle">
            {{ $displayInformation['name'] }}
        </x-slot>

        <div class="alert bg-base-300 text-sm my-4">
            <i class="fa-solid fa-book-open text-info"></i>
            <span>{{ $displayInformation['description'] }}</span>
        </div>

        <form action="{{ route('backups.store') }}" method="post" id="create-form">
            @csrf

            <input type="hidden" name="provider" value="{{ $provider }}" />

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label">{{ __('Label') }}</x-slot>

                <x-input name="label" :value="old('label')" placeholder="My Backup" autofocus />
            </x-form-field>

            @if ($providerTemplate)
                <div class="divider">{{ __('Config') }}</div>

                @include($providerTemplate)
            @endif
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('backups.index')) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Create') }}" form="create-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>