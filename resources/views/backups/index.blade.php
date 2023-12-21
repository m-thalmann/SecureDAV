<x-app-layout :title="__('Backups')">
    @include('backups.partials.configurations')

    <div class="spacer h-1"></div>

    @include('backups.partials.available-providers')
</x-app-layout>