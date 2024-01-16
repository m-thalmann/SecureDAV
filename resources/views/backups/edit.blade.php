<x-app-layout :title="__('Edit Backup Configuration')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-pen">
            {{ __('Edit Backup Configuration') }}
        </x-slot>

        <x-slot name="subtitle">
            {{ $displayInformation['name'] }}
        </x-slot>

        <form action="{{ route('backups.update', [$configuration]) }}" method="post" id="edit-form" x-data="{ editConfig: false }">
            @method('PUT')
            @csrf

            <x-form-field name="label" class="md:w-2/3">
                <x-slot name="label">{{ __('Label') }}</x-slot>

                <x-input name="label" :value="$configuration->label" placeholder="My Backup" autofocus />
            </x-form-field>

            <div class="form-control w-fit mb-4">
                <label class="label cursor-pointer gap-4">
                    <span class="label-text">{{ __('Active') }}</span>
                    <input type="checkbox" @checked($configuration->active) class="checkbox checkbox-secondary" name="active" />
                </label>
            </div>

            <input type="hidden" name="edit-config" x-bind:value="editConfig ? 'true' : 'false'">

            @if ($providerTemplate)
                <button class="btn btn-neutral btn-sm" x-on:click="editConfig = true" x-show="!editConfig">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    {{ __('Edit Config') }}
                </button>

                <template x-if="editConfig">
                    <div>
                        <div class="divider">{{ __('Config') }}</div>
                        @include($providerTemplate)
                    </div>
                </template>
            @endif
        </form>

        <x-slot name="actions">
            <a href="{{ previousUrl(fallback: route('backups.show', [$configuration])) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
            <input type="submit" value="{{ __('Save') }}" form="edit-form" class="btn btn-primary">
        </x-slot>
    </x-card>
</x-app-layout>