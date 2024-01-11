<x-card id="backups" collapsible>
    <x-slot name="title" :amount="$file->backupConfigurations->count()">
        {{ __('Backups') }}
    </x-slot>

    <div class="actions flex gap-4 items-center my-4">
        <a href="{{ route('backups.index') }}" class="btn btn-neutral btn-sm">
            <i class="fa-solid fa-arrow-right mr-2"></i>
            {{ __('Browse backups') }}
        </a>
    </div>

    @if ($file->backupConfigurations->count() > 0)
        <div class="overflow-auto w-full bg-base-100 rounded-md max-h-[25em]">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Provider') }}</th>
                        <th>{{ __('Last run') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($file->backupConfigurations as $configuration)
                        <tr>
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
                                <x-timestamp :timestamp="$configuration->last_run_at" :fallback="__('Never')" />
                            </td>
                            <td>
                                <x-dropdown
                                    :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                    width="w-56"
                                >
                                    <form
                                        method="POST"
                                        action="{{ route('backups.files.destroy', [$configuration, $file]) }}"
                                        onsubmit="return confirm(`{{ __('Are you sure you want to remove this file from the backup?') }}`)"
                                    >
                                        @method('DELETE')
                                        @csrf

                                        <li>
                                            <button class="hover:bg-error hover:text-error-content">
                                                <i class="fa-solid fa-circle-minus w-6"></i>
                                                {{ __('Remove from backup') }}
                                            </button>
                                        </li>
                                    </form>
                                </x-dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <span class="italic text-base-content/70">{{ __('This file is not being backed up') }}</span>
    @endif
</x-card>