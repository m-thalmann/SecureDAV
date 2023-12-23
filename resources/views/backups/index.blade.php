<x-app-layout :title="__('Backups')">
    <div class="alert bg-base-300">
        <i class="fa-solid fa-circle-info text-info"></i>
        <span>{{ __('The backups will be stored unencrypted on the target') }}</span>
    </div>

    @include('backups.partials.configurations')

    <div class="spacer h-1"></div>

    @include('backups.partials.available-providers')
</x-app-layout>