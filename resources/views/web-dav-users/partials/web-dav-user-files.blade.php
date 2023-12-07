<x-card id="files">
    <x-slot name="title" :amount="$webDavUser->files->count()">
        {{ __('Accessible files') }}
    </x-slot>

    <div class="actions my-4">
        <a href="#" class="btn btn-neutral btn-sm">
            <i class="fa-solid fa-file-circle-plus mr-2"></i>
            {{ __('Add access to file') }}
        </a>
    </div>

    <div class="overflow-auto w-full bg-base-100 rounded-md">
        <x-files-table.table :filesCount="count($webDavUser->files)" :showCountSummary="false">
            @foreach ($webDavUser->files as $file)
                <x-files-table.file-row :file="$file" :hover="false" :includeParentDirectory="true">
                    <x-slot name="actions">
                        <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)">
                            <li>
                                <button class="hover:bg-error hover:text-error-content">
                                    <i class="fa-solid fa-user-slash w-6"></i>
                                    {{ __('Revoke access') }}
                                </button>
                            </li>
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