<x-app-layout :title="__('File trash')">
    <x-header-title iconClass="fa-solid fa-trash">
        <x:slot name="title">
            {{ __('File trash') }}

            <small>({{ $files->total() }})</small>
        </x:slot>
    </x-header-title>

    <div class="overflow-auto w-full">
        <x-files-table.table :filesCount="count($files)" :showCountSummary="false" :deletedAtColumn="true">
            @foreach ($files as $file)
                <x-files-table.file-row :file="$file" :deletedAtColumn="true" :link="false">
                    <x-slot name="actions">
                        <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)" width="w-52">
                            <form method="POST" action="{{ route('files.trash.restore', [$file]) }}">
                                @method('PUT')
                                @csrf
                                
                                <li>
                                    <button class="hover:bg-success hover:text-success-content">
                                        <i class="fa-solid fa-trash-arrow-up w-6"></i>
                                        {{ __('Restore') }}
                                    </button>
                                </li>
                            </form>

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
</x-app-layout>