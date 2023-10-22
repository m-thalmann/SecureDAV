<x-app-layout :title="$accessGroup->label . ' - ' . __('Access group')">
    <x-header-title iconClass="fa-solid fa-user-group">
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

    @if (session('generated-password'))
        <div class="alert max-sm:rounded-none md:w-fit">
            <i class="fa-solid fa-key text-success"></i>
            <span>
                {{ __('Generated password') }}: <span class="font-mono ml-2 inline-block blur" id="generated-password">{{ session('generated-password') }}</span>
            </span>

            <div>
                <button
                    class="btn btn-circle btn-sm"
                    onclick="document.getElementById('generated-password').classList.toggle('blur')"
                >
                    <i class="fa-solid fa-eye"></i>
                </button>

                <x-copy-button :data="session('generated-password')" />
            </div>
        </div>
    @endif

    <x-card id="users">
        <x-slot name="title" :amount="$accessGroup->users->count()">
            {{ __('Group users') }}
        </x-slot>

        <div class="actions my-4">
            <a href="{{ route('access-groups.access-group-users.create', ['access_group' => $accessGroup->uuid]) }}" class="btn btn-neutral btn-sm">
                <i class="fa-solid fa-user-plus mr-2"></i>
                {{ __('Create group user') }}
            </a>
        </div>

        <div class="overflow-auto w-full bg-base-100 rounded-md max-h-[25em]">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Username') }}</th>
                        <th>{{ __('Last access') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($accessGroup->users as $accessGroupUser)
                        <tr>
                            <td>{{ $accessGroupUser->label }}</td>
                            <td class="font-mono">
                                <span class="select-all">{{ $accessGroupUser->username }}</span>

                                <x-copy-button :data="$accessGroupUser->username" />
                            </td>
                            <td>
                                <span class="tooltip" data-tip="{{ $accessGroupUser->last_access ?? '-' }}">{{ $accessGroupUser->last_access?->diffForHumans() ?? '-' }}</span>
                            </td>
                            <td>
                                <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 3)" width="w-52">
                                    <li>
                                        <a href="{{ route('access-group-users.edit', ['access_group_user' => $accessGroupUser->username]) }}">
                                            <i class="fas fa-edit mr-2"></i>
                                            {{ __('Edit group user') }}
                                        </a>
                                    </li>

                                    <form
                                        method="POST"
                                        action="{{ route('access-group-users.reset-password', ['access_group_user' => $accessGroupUser->username]) }}"
                                    >
                                        @csrf
                                        
                                        <li>
                                            <button>
                                                <i class="fa-solid fa-rotate-left mr-2"></i>
                                                {{ __('Reset password') }}
                                            </button>
                                        </li>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('access-group-users.destroy', ['access_group_user' => $accessGroupUser->username]) }}"
                                        onsubmit="return confirm('{{ __('Are you sure you want to delete this group user?') }}')"
                                    >
                                        @method('DELETE')
                                        @csrf
                                        
                                        <li>
                                            <button class="hover:bg-error hover:text-error-content">
                                                <i class="fas fa-trash mr-2"></i>
                                                {{ __('Delete group user') }}
                                            </button>
                                        </li>
                                    </form>
                                </x-dropdown>
                            </td>
                        </tr>
                    @endforeach

                    @if ($accessGroup->users->count() === 0)
                        <tr>
                            <td colspan="4" class="text-center italic text-base-content/70">{{ __('No users for this access group') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-card>

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
                                <form method="POST" action="{{ route('access-groups.files.destroy', ['access_group' => $accessGroup->uuid, 'file' => $file->uuid]) }}" onsubmit="return confirm('{{ __('Are you sure you want to revoke access to this file?') }}')">
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
</x-app-layout>