<x-app-layout :title="$accessGroup->label . ' - ' . __('Access group')">
    <x-header-title iconClass="fa-solid fa-shield-alt">
        <x:slot name="title">
            {{ $accessGroup->label }}
        </x:slot>

        <x-slot name="subtitle">
            <span class="tooltip" data-tip="{{ __('Created') }}">{{ $accessGroup->created_at }}</span>
        </x-slot>

        <x-slot name="suffix">
            @if ($accessGroup->active)
                <span class="tooltip" data-tip="{{ __('Active') }}">
                    <i class="fa-solid fa-circle-check text-success text-xl"></i>
                </span>
            @else
                <span class="tooltip" data-tip="{{ __('Inactive') }}">
                    <i class="fa-solid fa-circle-xmark text-error text-xl"></i>
                </span>
            @endif

            @if ($accessGroup->readonly)
                <span class="tooltip" data-tip="{{ __('Read-Only') }}">
                    <i class="fa-solid fa-book-open text-secondary text-xl"></i>
                </span>
            @else
                <span class="tooltip" data-tip="{{ __('Read and write') }}">
                    <i class="fa-solid fa-file-pen text-primary text-xl"></i>
                </span>
            @endif

            <span class="flex-1"></span>

            <x-dropdown align="end" width="w-56">
                <li>
                    <a href="{{ route('access-groups.edit', ['access_group' => $accessGroup->uuid]) }}">
                        <i class="fas fa-edit mr-2"></i>
                        {{ __('Edit access group') }}
                    </a>
                </li>

                <form
                    method="POST"
                    action="{{ route('access-groups.destroy', ['access_group' => $accessGroup->uuid]) }}"
                    onsubmit="return confirm('{{ __('Are you sure you want to delete this access group and all of it\'s users?') }}')"
                >
                    @method('DELETE')
                    @csrf
                    
                    <li>
                        <button class="hover:bg-error hover:text-error-content">
                            <i class="fas fa-trash mr-2"></i>
                            {{ __('Delete access group') }}
                        </button>
                    </li>
                </form>
            </x-dropdown>
        </x-slot>
    </x-header-title>

    <x-card id="files">
        <x-slot name="title" class="mb-4">
            {{ __('Accessible files') }}
            <small class="font-normal">({{ $accessGroup->files->count() }})</small>
        </x-slot>

        <div class="overflow-auto w-full bg-base-100 rounded-md">
            <x-files-table.table :filesCount="count($accessGroup->files)" :showCountSummary="false">
                @foreach ($accessGroup->files as $file)
                    <x-files-table.file-row :file="$file">
                        <x-slot name="actions">
                            <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)">
                                <li>
                                    <a href="#" class="hover:bg-error hover:text-error-content">
                                        <i class="fa-solid fa-user-slash mr-2"></i>
                                        {{ __('Revoke access') }}
                                    </a>
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
</x-app-layout>