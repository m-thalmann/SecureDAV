<x-app-layout :title="$configuration->label . ' - ' . __('Backups')">
    <x-header-title :iconClass="$displayInformation['icon'] ?? null" :iconUrl="$displayInformation['iconUrl'] ?? null">
        <x:slot name="title">
            {{ $configuration->label }}
        </x:slot>

        <x-slot name="subtitle">
            {{ __('Last run') }}:
            <x-timestamp :timestamp="$configuration->last_run_at" :fallback="__('Never')" />
        </x-slot>

        <x-slot name="suffix">
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

    <x-card id="files">
        <x-slot name="title" :amount="$configuration->files->count()">
            {{ __('Backed up files') }}
        </x-slot>

        <div class="actions my-4">
            <a href="#" class="btn btn-neutral btn-sm">
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
                        <th>{{ __('Last successfull backup') }}</th>
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
                            <td class="pl-2 flex items-center gap-2">
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

                                @if ($file->isEncrypted)
                                    <span class="tooltip" data-tip="{{ __('Encrypted') }}">
                                        <i class="fa-solid fa-lock text-primary text-xs"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="max-sm:hidden text-right">
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
                                    <span class="text-error">
                                        {{ $file->pivot->last_error }}
                                    </span>
                                    <br>
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
                                    <li>
                                        <button class="hover:bg-error hover:text-error-content">
                                            <i class="fa-solid fa-circle-minus w-6"></i>
                                            {{ __('Remove from backup') }}
                                        </button>
                                    </li>
                                </x-dropdown>
                            </td>
                        </tr>
                    @endforeach

                    @if (count($configuration->files) === 0)
                        <tr>
                            <td colspan="7" class="text-center italic text-base-content/70">{{ __('No files to backup') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>