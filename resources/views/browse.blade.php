<x-app-layout :title="__('Browse files')">
    <div class="files-breadcrumbs flex items-center px-4">
        <x-breadcrumbs :directories="$breadcrumbs">
            <li></li>
        </x-breadcrumbs>

        <x-dropdown :align="count($breadcrumbs) > 2 ? 'end' : 'start'">
            <x-slot:icon><i class="fas fa-add"></i></x-slot:icon>

            <li>
                <a href="{{ route('directories.create') . ($currentDirectory ? "?directory={$currentDirectory->uuid}" : '') }}">
                    <i class="fa-solid fa-folder-plus w-6"></i>
                    {{ __('New directory') }}
                </a>
            </li>
            <li>
                <a href="{{ route('files.create') . ($currentDirectory ? "?directory={$currentDirectory->uuid}" : '') }}">
                    <i class="fa-solid fa-file-circle-plus w-6"></i>
                    {{ __('New file') }}
                </a>
            </li>
        </x-dropdown>

        @if ($currentDirectory)
            <span class="flex-1"></span>

            <x-dropdown align="end">
                <li>
                    <a href="{{ route('directories.edit', ['directory' => $currentDirectory->uuid]) }}">
                        <i class="fas fa-edit mr-2"></i>
                        {{ __('Edit directory') }}
                    </a>
                </li>

                <form method="POST" action="{{ route('directories.destroy', ['directory' => $currentDirectory->uuid]) }}">
                    @method('DELETE')
                    @csrf
                    
                    <li>
                        <button class="hover:bg-error hover:text-error-content">
                            <i class="fas fa-trash mr-2"></i>
                            {{ __('Delete directory') }}
                        </button>
                    </li>
                </form>
            </x-dropdown>
        @endif
    </div>

    <div class="overflow-auto w-full">
        <table class="table">
            <thead>
                <tr>
                    <th class="w-0 pr-2"></th>
                    <th class="pl-0">{{ __('Name') }}</th>
                    <th class="max-sm:hidden">{{ __('Size') }}</th>
                    <th>{{ __('Current version') }}</th>
                    <th>{{ __('Last updated') }}</th>
                    <th class="w-0"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($directories as $directory)
                    <tr class="hover">
                        <td class="pr-2">
                            <i class="fas fa-folder text-secondary"></i>
                        </td>
                        <td class="pl-0">
                            <a
                                href="{{ route('browse.index', ['directory' => $directory->uuid]) }}"
                                class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis"
                            >
                                {{ $directory->name }}
                            </a>
                        </td>
                        <td class="max-sm:hidden text-right">-</td>
                        <td>-</td>
                        <td>
                            <span class="tooltip" data-tip="{{ $directory->updated_at }}">{{ $directory->updated_at->diffForHumans() }}</span>
                        </td>
                        <td class="flex justify-end">
                            <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned($loop->index, count($files) + $loop->count, 2)">
                                <li>
                                    <a href="{{ route('directories.edit', ['directory' => $directory->uuid]) }}">
                                        <i class="fas fa-edit mr-2"></i>
                                        {{ __('Edit directory') }}
                                    </a>
                                </li>

                                <form method="POST" action="{{ route('directories.destroy', ['directory' => $directory->uuid]) }}">
                                    @method('DELETE')
                                    @csrf
                                    
                                    <li>
                                        <button class="hover:bg-error hover:text-error-content">
                                            <i class="fas fa-trash mr-2"></i>
                                            {{ __('Delete directory') }}
                                        </button>
                                    </li>
                                </form>
                            </x-dropdown>
                        </td>
                    </tr>
                @endforeach

                @foreach ($files as $file)
                    <tr class="hover">
                        <td class="pr-2">
                            <i class="{{ $file->fileIcon }} text-xl"></i>
                        </td>
                        <td class="pl-0">
                            <a
                                href="{{ route('files.show', ['file' => $file->uuid]) }}"
                                class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis"
                            >{{ $file->fileName }}</a>

                            @if ($file->isEncrypted)
                                <span class="tooltip ml-2" data-tip="{{ __('Encrypted') }}">
                                    <i class="fa-solid fa-lock text-primary text-xs"></i>
                                </span>
                            @endif
                        </td>
                        <td class="max-sm:hidden text-right">
                            @if ($file->latestVersion)
                                {{ formatBytes($file->latestVersion->bytes) }}
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
                            <span class="tooltip" data-tip="{{ $file->latestVersion?->updated_at ?? __('No versions yet') }}">{{ $file->latestVersion?->updated_at?->diffForHumans() ?? '-' }}</span>
                        </td>
                        <td class="flex justify-end gap-2 items-center">
                            @if ($file->latestVersion !== null)
                                <a href="{{ route('files.versions.latest.show', ['file' => $file]) }}" class="btn btn-sm btn-square">
                                    <i class="fas fa-download"></i>
                                </a>
                            @endif

                            <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned(count($directories) + $loop->index, count($directories) + $loop->count, 2)">
                                <li>
                                    <a href="{{ route('files.edit', ['file' => $file->uuid]) }}">
                                        <i class="fas fa-edit mr-2"></i>
                                        {{ __('Edit file') }}
                                    </a>
                                </li>

                                <form method="POST" action="{{ route('files.destroy', ['file' => $file->uuid]) }}" onsubmit="return confirm('{{ __('Are you sure you want to move this file to trash?') }}')">
                                    @method('DELETE')
                                    @csrf
                                    
                                    <li>
                                        <button class="hover:bg-error hover:text-error-content">
                                            <i class="fas fa-trash mr-2"></i>
                                            {{ __('Move file to trash') }}
                                        </button>
                                    </li>
                                </form>
                            </x-dropdown>
                        </td>
                    </tr>
                @endforeach

                @if (count($directories) === 0 && count($files) === 0)
                    <tr>
                        <td colspan="6" class="text-center italic text-base-content/70">{{ __('This directory is empty') }}</td>
                    </tr>
                @else
                    <tr>
                        <td></td>
                        <td colspan="5" class="text-sm text-base-content/50 pl-0">
                            {{ trans_choice('{1} 1 directory|[2,*] :count directories', count($directories)) }},
                            {{ trans_choice('{1} 1 file|[2,*] :count files', count($files)) }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</x-app-layout>