<x-card id="files">
    <x-slot name="title" :amount="$accessGroup->files->count()">
        {{ __('Accessible files') }}
    </x-slot>

    <div class="actions my-4">
        <a href="{{ route('access-groups.files.create', ['access_group' => $accessGroup->uuid]) }}" class="btn btn-neutral btn-sm">
            <i class="fa-solid fa-file-circle-plus mr-2"></i>
            {{ __('Add access to file') }}
        </a>
    </div>

    <div class="overflow-auto w-full bg-base-100 rounded-md">
        <x-files-table.table :filesCount="count($accessGroup->files)" :showCountSummary="false">
            @foreach ($accessGroup->files as $file)
                <x-files-table.file-row :file="$file">
                    <x-slot name="actions">
                        <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)">
                            <form
                                method="POST"
                                action="{{ route('access-groups.files.destroy', ['access_group' => $accessGroup->uuid, 'file' => $file->uuid]) }}"
                                onsubmit="return confirm(`{{ __('Are you sure you want to revoke access to this file?') }}`)"
                            >
                                @method('DELETE')
                                @csrf
                                
                                <li>
                                    <button class="hover:bg-error hover:text-error-content">
                                        <i class="fa-solid fa-user-slash mr-2"></i>
                                        {{ __('Revoke access') }}
                                    </button>
                                </li>
                            </form>
                        </x-dropdown>
                    </x-slot>
                </x-files-table.file-row>
            @endforeach

            <x-slot name="noItemsContent">
                {{ __('No accessible files') }}
            </x-slot>
        </x-files-table.table>
    </div>
</x-card>