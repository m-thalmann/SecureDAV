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
        <x-files-table.table :directoriesCount="count($directories)" :filesCount="count($files)">
            @foreach ($directories as $directory)
                <x-files-table.directory-row :directory="$directory">
                    <x-slot:actions>
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
                    </x-slot:actions>
                </x-files-table.directory-row>
            @endforeach

            @foreach ($files as $file)
                <x-files-table.file-row :file="$file">
                    <x-slot:actions>
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
                    </x-slot:actions>
                </x-files-table.file-row>
            @endforeach

            <x-slot:noItemsContent>
                {{ __('This directory is empty') }}
            </x-slot:noItemsContent>
        </x-files-table.table>
    </div>
</x-app-layout>