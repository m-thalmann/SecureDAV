<x-app-layout :title="__('File trash')">
    <x-header-title iconClass="fa-solid fa-trash">
        <x:slot name="title">
            {{ __('File trash') }}

            <small>({{ $files->total() }})</small>
        </x:slot>
    </x-header-title>

    <div class="overflow-auto w-full min-h-[12em]">
        <x-files-table.table :filesCount="count($files)" :showCountSummary="false" :deletedAtColumn="true">
            @foreach ($files as $file)
                <x-files-table.file-row :file="$file" :deletedAtColumn="true" :link="false" :includeParentDirectory="true">
                    <x-slot name="actions">
                        <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 3)" width="w-52">
                            <form method="POST" action="{{ route('files.trash.restore', [$file]) }}" id="restore-file-form-{{ $file->id }}">
                                @method('PUT')
                                @csrf
                                
                                <li>
                                    <button class="hover:bg-success hover:text-success-content">
                                        <i class="fa-solid fa-trash-arrow-up w-6"></i>
                                        {{ __('Restore') }}
                                    </button>
                                </li>

                                <input type="hidden" value="" name="rename" id="restore-rename-file-{{ $file->id }}" />
                            </form>

                            <li>
                                <button class="hover:bg-success hover:text-success-content" onclick="renameAndRestoreFile({{ $file->id }}, '{{ e($file->name) }}')">
                                    <i class="fa-solid fa-trash-arrow-up w-6"></i>
                                    {{ __('Rename and restore') }}
                                </button>
                            </li>

                            <form
                                method="POST"
                                action="{{ route('files.trash.destroy', [$file]) }}"
                                onsubmit="return confirm(`{{ __('Are you sure you want to permanently delete this file?') }}`)"
                            >
                                @method('DELETE')
                                @csrf
                                
                                <li>
                                    <button class="hover:bg-error hover:text-error-content">
                                        <i class="fa-solid fa-trash-can w-6"></i>
                                        {{ __('Permanently delete') }}
                                    </button>
                                </li>
                            </form>
                        </x-dropdown>
                    </x-slot>
                </x-files-table.file-row>
            @endforeach

            <x-slot name="noItemsContent">
                {{ __('The trash is empty') }}
            </x-slot>
        </x-files-table.table>
    </div>

    {{ $files->links() }}

    @push('scripts')
        <script>
            function renameAndRestoreFile(fileId, currentFileName) {
                var name = prompt('{{ __('New file name') }}', currentFileName);

                if(!name) {
                    return;
                }

                document.getElementById('restore-rename-file-' + fileId).value = name;
                document.getElementById('restore-file-form-' + fileId).submit();
            }
        </script>
    @endpush
</x-app-layout>