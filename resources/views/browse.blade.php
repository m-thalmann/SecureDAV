<x-app-layout :title="__('Browse files')">
    <div class="files-breadcrumbs flex items-center px-4">
        <x-breadcrumbs :directories="$breadcrumbs">
            <li></li>
        </x-breadcrumbs>

        <div @class([
            'dropdown',
            'dropdown-end' => count($breadcrumbs) > 2,
        ])>
            <label tabindex="0" class="btn btn-sm btn-circle">
                <i class="fas fa-add"></i>
            </label>
            <ul tabindex="0" class="dropdown-content z-[1] menu p-2 mt-1 shadow bg-base-300 rounded-box w-44">
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
            </ul>
        </div>

        @if ($currentDirectory)
            <span class="flex-1"></span>

            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-sm btn-circle">
                    <i class="fa-solid fa-ellipsis"></i>
                </label>
                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 mt-1 shadow bg-base-300 rounded-box w-48">
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
                </ul>
            </div>
        @endif
    </div>

    <div class="overflow-auto w-full">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th class="max-sm:hidden">{{ __('Size') }}</th>
                    <th>{{ __('Current version') }}</th>
                    <th>{{ __('Last updated') }}</th>
                    <th class="w-0"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($directories as $directory)
                    <tr class="hover">
                        <td>
                            <a href="{{ route('browse.index', ['directory' => $directory->uuid]) }}" class="flex items-center group">
                                <i class="fas fa-folder text-secondary w-6"></i>
                                <span class="group-hover:underline max-w-[48ch] overflow-hidden text-ellipsis">
                                    {{ $directory->name }}
                                </span>
                            </a>
                        </td>
                        <td class="max-sm:hidden">-</td>
                        <td>-</td>
                        <td>
                            <span class="tooltip" data-tip="{{ $directory->updated_at }}">{{ $directory->updated_at->diffForHumans() }}</span>
                        </td>
                        <td></td>
                    </tr>
                @endforeach

                @foreach ($files as $file)
                    <tr class="hover">
                        <td>
                            <a href="{{ route('files.show', ['file' => $file->uuid]) }}" class="flex items-center group">
                                <i class="{{ $file->fileIcon }} text-xl w-[1.25em] mr-1"></i>
                                <span class="group-hover:underline">
                                    {{ $file->fileName }}
                                </span>
                            </a>
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
                        <td>
                            @if ($file->latestVersion !== null)
                                <a href="{{ route('files.file-versions.latest.show', ['file' => $file]) }}" class="btn btn-sm btn-square">
                                    <i class="fas fa-download"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach

                @if (count($directories) === 0 && count($files) === 0)
                    <tr>
                        <td colspan="5" class="text-center italic text-base-content/70">{{ __('This directory is empty') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</x-app-layout>