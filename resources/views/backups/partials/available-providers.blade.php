<x-header-title iconClass="fa-solid fa-gears">
    <x:slot name="title">
        {{ __('Available Backup Providers') }} <small>({{ count($providers) }})</small>
    </x:slot>
</x-header-title>

<div class="providers grid grid-cols-4 gap-4 max-h-64 overflow-auto">
    @foreach ($providers as $provider)
        <div class="rounded-lg bg-base-200 flex flex-row gap-4 items-center p-4 shadow-lg relative">
            <div class="icon">
                <x-backup-provider-icon :provider="$provider['class']" large="true" />
            </div>

            <div class="body mr-6">
                <h3 class="text-base">{{ $provider['displayInformation']['name'] }}</h3>
                <p class="text-sm text-base-content/75 font-light line-clamp-3" title="{{ $provider['displayInformation']['description'] }}">{{ $provider['displayInformation']['description'] }}</p>
            </div>

            <a
                href="{{ route('backups.create') }}?provider={{ $provider['alias'] ?? $provider['class'] }}"
                class="absolute bottom-0 right-0 w-10 h-10 bg-secondary text-secondary-content hover:bg-secondary-focus rounded-br-lg rounded-tl-lg flex items-center justify-center transition-colors"
            >
                <i class="fa-solid fa-plus"></i>
            </a>
        </div>
    @endforeach
</div>