<x-card>
    <x-slot name="title" icon="fa-solid fa-rotate" :amount="$configurations->total()" class="mb-4">
        {{ __('Configured Backups') }}
    </x-slot>

    <div class="overflow-auto w-full bg-base-100 rounded-md">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Label') }}</th>
                    <th>{{ __('Provider') }}</th>
                    <th>{{ __('Files') }}</th>
                    <th>{{ __('Last run') }}</th>
                    <th class="w-0">{{ __('Next scheduled run') }}</th>
                    <th class="w-0 text-center">{{ __('Store with version') }}</th>
                    <th class="w-0 text-center">{{ __('Status') }}</th>
                    <th class="w-0"></th>
                </tr>
            </thead>

            <tbody>
                @foreach ($configurations as $configuration)
                    <tr @class([
                        'text-base-content/50' => !$configuration->active,
                    ])>
                        <td>
                            <a href="{{ route('backups.show', [$configuration]) }}" class="link underline-offset-2">{{ $configuration->label }}</a>
                        </td>
                        <td>
                            <span class="flex gap-2 items-center">
                                <x-backup-provider-icon :configuration="$configuration" />

                                <span class="underline underline-offset-4 decoration-dashed" title="{{ $configuration->provider_class::getDisplayInformation()['description'] }}">
                                    {{ $configuration->provider_class::getDisplayInformation()['name'] }}
                                </span>
                            </span>
                        </td>
                        <td>
                            {{ $configuration->files_count }}
                        </td>
                        <td>
                            <x-timestamp :timestamp="$configuration->last_run_at" :fallback="__('Never')" />
                        </td>
                        <td>
                            <x-timestamp :timestamp="$configuration->schedule?->getNextRunDate()" />
                        </td>
                        <td class="text-center">
                            <input type="checkbox" @checked($configuration->store_with_version) class="checkbox checkbox-sm checkbox-primary cursor-not-allowed align-middle" tabindex="-1" onclick="return false;" />
                        </td>
                        <td class="text-center">
                            @if (!$configuration->active)
                                <span class="tooltip" data-tip="{{ __('Inactive') }}">
                                    <i class="fa-solid fa-ban text-error text-lg"></i>
                                </span>
                            @elseif ($configuration->started_at !== null)
                                <span class="tooltip" data-tip="{{ __('Running') }}">
                                    <span class="loading loading-ring loading-md text-primary"></span>
                                </span>
                            @else
                                @if ($configuration->up_to_date)
                                    <span class="tooltip" data-tip="{{ __('Up to date') }}">
                                        <i class="fa-solid fa-circle-check text-success text-lg"></i>
                                    </span>
                                @else
                                    <span class="tooltip" data-tip="{{ __('Outdated') }}">
                                        <i class="fa-solid fa-circle-exclamation text-error text-lg"></i>
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td>
                            <x-dropdown
                                :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                width="w-52"
                            >
                                <li>
                                    <a href="{{ route('backups.edit', [$configuration]) }}">
                                        <i class="fas fa-edit w-6"></i>
                                        {{ __('Edit') }}
                                    </a>
                                </li>

                                <form
                                    method="POST"
                                    action="{{ route('backups.destroy', [$configuration]) }}"
                                    onsubmit="return confirm(`{{ __('Are you sure you want to delete this backup configuration?') }}`)"
                                >
                                    @method('DELETE')
                                    @csrf
                                    
                                    <li>
                                        <button class="hover:bg-error hover:text-error-content">
                                            <i class="fas fa-trash w-6"></i>
                                            {{ __('Delete') }}
                                        </button>
                                    </li>
                                </form>
                            </x-dropdown>
                        </td>
                    </tr>
                @endforeach

                @if (count($configurations) === 0)
                    <tr>
                        <td colspan="100" class="text-center italic text-base-content/70">{{ __('No backups configured yet') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</x-card>

{{ $configurations->links() }}