<x-app-layout :title="$configuration->label . ' - ' . __('Backups')">
    <x-header-title :iconClass="$displayInformation['icon'] ?? null" :iconUrl="$displayInformation['iconUrl'] ?? null">
        <x:slot name="icon">
            <x-backup-provider-icon :configuration="$configuration" :large="true" />
        </x:slot>

        <x:slot name="title">
            {{ $configuration->label }}
        </x:slot>

        <x-slot name="subtitle">
            {{ __('Last run') }}:
            <x-timestamp :timestamp="$configuration->last_run_at" :fallback="__('Never')" />
        </x-slot>

        <x-slot name="suffix">
            @if ($configuration->store_with_version)
                <span class="tooltip" data-tip="{{ __('Stores version in file name') }}">
                    <i class="fa-solid fa-clock-rotate-left text-primary text-lg"></i>
                </span>
            @endif

            <span class="flex-1"></span>

            <x-dropdown align="end" width="w-36">
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
        </x-slot>
    </x-header-title>

    <form action="{{ route('backups.backup', [$configuration]) }}" method="post">
        @csrf

        <div class="flex gap-4 items-center px-4 sm:px-0">
            <button class="btn btn-primary" @if (!$configuration->active) disabled @endif>
                <i class="fa-solid fa-play"></i>
                {{ __('Run backup') }}
            </button>

            @if (!$configuration->active)
                <span class="text-error">
                    <i class="fa-solid fa-ban mr-2"></i>
                    {{ __('This backup is currently inactive.') }}
                </span>
            @endif

            @if ($configuration->started_at !== null)
                <div class="alert w-fit">
                    <span class="loading loading-ring loading-md text-primary"></span>

                    <span>
                        {{ __('This backup is currently running. Starting it again may lead to unwanted side effects.') }}
                        <div class="text-base-content/50 text-xs"><x-timestamp :timestamp="$configuration->started_at" /></div>
                    </span>
                </div>
            @endif
        </div>
    </form>

    <x-card id="config" collapsible>
        <x-slot name="title">
            {{ __('Config') }}
        </x-slot>

        <pre><code class="language-json rounded-lg shadow-lg">{{ $jsonConfig }}</code></pre>
    </x-card>

    <x-card id="schedule" collapsible>
        <x-slot name="title">
            {{ __('Schedule') }}
        </x-slot>

        <x-slot name="collapsedTitleSuffix">
            @if ($configuration->cron_schedule === null)
                {{ __('None') }}
            @else
                {{ $configuration->schedule->getName() ?? __('Custom') }}
            @endif
        </x-slot>

        <div class="mb-2">
            <a href="{{ route('backups.schedule.edit', [$configuration]) }}" class="btn btn-sm btn-neutral">
                <i class="fas fa-pen mr-2"></i>
                {{ __('Edit schedule') }}
            </a>
        </div>

        @if ($configuration->cron_schedule === null)
            <i class="text-base-content/50">
                {{ __('This backup is not scheduled to run automatically.') }}
            </i>
        @else
            <span class="font-bold">{{ __('Selected') }}:</span>
            <div class="mb-4">{{ $configuration->schedule->getName() ?? __('Custom') }}</div>

            <span class="font-bold">{{ __('Next runs') }}:</span>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($scheduleInfo['nextRunDates'] as $index => $nextRun)
                    <div class="flex gap-4 items-center bg-base-300 px-4 py-2 rounded-md shadow-md">
                        <span class="font-bold">{{ $index + 1 }}.</span>
                        <span class="h-2/3 w-[1px] bg-base-content/25"></span>
                        <div class="flex flex-col">
                            <span class="block">{{ $nextRun->diffForHumans() }}</span>
                            <small class="text-xs text-base-content/75">{{ $nextRun }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    <x-card id="files">
        <x-slot name="title" :amount="$configuration->files->count()">
            {{ __('Backed up files') }}
        </x-slot>

        <div class="actions mb-4">
            <a href="{{ route('backups.files.create', [$configuration]) }}" class="btn btn-neutral btn-sm">
                <i class="fa-solid fa-file-circle-plus mr-2"></i>
                {{ __('Add file to backup') }}
            </a>
        </div>

        <div class="overflow-auto w-full bg-base-100 rounded-md">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-0 pr-0"></th>
                        <th class="pl-2">{{ __('Name') }}</th>
                        <th class="max-sm:hidden">{{ __('Size') }}</th>
                        <th>{{ __('Current version') }}</th>
                        <th>{{ __('Last updated') }}</th>
                        <th>{{ __('Last backup error') }}</th>
                        <th>{{ __('Last successful backup') }}</th>
                        <th class="w-0">{{ __('Status') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($configuration->files as $file)
                        <tr>
                            <td class="text-center pr-0">
                                <i class="{{ $file->fileIcon }} text-xl align-middle"></i>
                            </td>
                            <td class="pl-2">
                                <div class="text-sm breadcrumbs">
                                    <ul>
                                        <li>
                                            <a
                                                href="{{ route('browse.index', [$file->directory]) }}"
                                                class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis text-base-content/50">
                                                @if ($file->directory)
                                                    {{ $file->directory->name }}
                                                @else
                                                    <i class="fas fa-home"></i>
                                                @endif
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                href="{{ route('files.show', [$file]) }}"
                                                class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis"
                                            >
                                                {{ $file->name }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td class="max-sm:hidden text-right whitespace-nowrap">
                                @if ($file->latestVersion)
                                    {{ Illuminate\Support\Number::fileSize($file->latestVersion->bytes, maxPrecision: 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if ($file->latestVersion)
                                    <i class="fa-solid fa-clock-rotate-left mr-1"></i>
                                    {{ $file->latestVersion->version }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <x-timestamp :timestamp="$file->fileLastUpdatedAt" :tooltipFallback="__('No versions yet')" />
                            </td>
                            <td>
                                @if ($file->pivot->last_error)
                                    <div class="flex items-center gap-2" x-data="{ open: false }">
                                        <span class="text-error max-w-xs" :class="open || 'line-clamp-2'">
                                            {{ $file->pivot->last_error }}
                                        </span>

                                        <button class="btn btn-xs btn-circle" @click="open = !open">
                                            <i class="fa-solid fa-chevron-up" x-show="open"></i>
                                            <i class="fa-solid fa-chevron-down" x-show="!open"></i>
                                        </button>
                                    </div>
                                    <i class="text-xs">
                                        <x-timestamp :timestamp="$file->pivot->last_error_at" />
                                    </i>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <x-timestamp :timestamp="$file->pivot->last_backup_at" :fallback="__('Never')" />
                            </td>
                            <td class="text-center">
                                @if ($file->latestVersion)
                                    @if ($file->latestVersion->checksum === $file->pivot->last_backup_checksum)
                                        <span class="tooltip" data-tip="{{ __('Up to date') }}">
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                        </span>
                                    @else
                                        <span class="tooltip" data-tip="{{ __('Outdated') }}">
                                            <i class="fa-solid fa-circle-exclamation text-error"></i>
                                        </span>
                                    @endif
                                @else
                                    <span class="tooltip" data-tip="{{ __('File has no version') }}">
                                        <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                                    </span>
                                @endif
                            </td>
                            <td>
                                <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)" width="w-56">
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

                    @if (count($configuration->files) === 0)
                        <tr>
                            <td colspan="100" class="text-center italic text-base-content/70">{{ __('No files to backup') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-card>

    @push('scripts')
        @vite(['resources/js/highlight.js'])
    @endpush
</x-app-layout>
