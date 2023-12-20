<x-app-layout :title="__('Backups')">
    <x-header-title iconClass="fa-solid fa-gears">
        <x:slot name="title">
            {{ __('Available Backup Providers') }} <small>({{ count($providers) }})</small>
        </x:slot>
    </x-header-title>

    <div class="providers grid grid-cols-4 gap-4 max-h-64 overflow-auto">
        @foreach ($providers as $provider)
            <div class="rounded-lg bg-base-200 flex flex-row gap-4 items-center p-4 shadow-lg relative">
                <div class="icon">
                    @isset ($provider['displayInformation']['iconUrl'])
                        <img src="{{ $provider['displayInformation']['iconUrl'] }}" class="w-10 h-10 max-w-none rounded-sm" />
                    @else
                        <i class="{{ $provider['displayInformation']['icon'] }} text-3xl"></i>
                    @endif
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

    <div class="spacer h-1"></div>

    <x-header-title iconClass="fa-solid fa-rotate">
        <x:slot name="title">
            {{ __('Configured Backups') }} <small>({{ count($configurations) }})</small>
        </x:slot>
    </x-header-title>

    <div class="configurations">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Provider') }}</th>
                        <th>{{ __('Files') }}</th>
                        <th>{{ __('Last run') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($configurations as $configuration)
                        <tr>
                            <td>
                                {{ $configuration->label ?? '-' }}
                            </td>
                            <td class="flex gap-2 items-center">
                                @isset ($configuration->provider_class::getDisplayInformation()['iconUrl'])
                                    <img src="{{ $configuration->provider_class::getDisplayInformation()['iconUrl'] }}" class="w-4 h-4 max-w-none rounded-sm" />
                                @else
                                    <i class="{{ $configuration->provider_class::getDisplayInformation()['icon'] }}"></i>
                                @endif

                                <span class="underline underline-offset-4 decoration-dashed" title="{{ $configuration->provider_class::getDisplayInformation()['description'] }}">
                                    {{ $configuration->provider_class::getDisplayInformation()['name'] }}
                                </span>
                            </td>
                            <td>
                                {{ $configuration->files_count }}
                            </td>
                            <td>
                                <span class="tooltip" data-tip="{{ $configuration->last_run_at ?? __('Never') }}">{{ $configuration->last_run_at?->diffForHumans() ?? __('Never') }}</span>
                            </td>
                            <td>
                                <x-dropdown
                                    :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                    width="w-52"
                                >
                                    <li>
                                        <a href="#">
                                            <i class="fas fa-edit w-6"></i>
                                            {{ __('Edit') }}
                                        </a>
                                    </li>

                                    <li>
                                        <a href="#" class="hover:bg-error hover:text-error-content">
                                            <i class="fas fa-trash w-6"></i>
                                            {{ __('Delete') }}
                                        </a>
                                    </li>
                                </x-dropdown>
                            </td>
                        </tr>
                    @endforeach

                    @if (count($configurations) === 0)
                        <tr>
                            <td colspan="4" class="text-center">
                                {{ __('No backups configured yet.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>