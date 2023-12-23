<x-app-layout :title="__('Edit Backup Configuration')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-pen">
            {{ __('Edit Backup Configuration') }}
        </x-slot>

        <x-slot name="subtitle">
            {{ $displayInformation['name'] }}
        </x-slot>

        <form action="{{ route('backups.update', [$configuration]) }}" method="post" id="edit-form">
            @method('PUT')
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label">{{ __('Label') }}</x-slot>

                <x-input name="label" :value="$configuration->label" placeholder="My Backup" autofocus />
            </x-form-field>

            @if ($providerTemplate)
                @include($providerTemplate)
            @endif
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('backups.show', [$configuration])) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>