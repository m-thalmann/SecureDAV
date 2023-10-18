<x-app-layout :title="($accessUser->label ?? $accessUser->username) . ' - ' . __('Access user')">
    <x-header-title iconClass="fa-solid fa-user-shield">
        <x:slot name="title" :class="$accessUser->label ? '' : 'font-mono'">
            {{ $accessUser->label ?? $accessUser->username }}
        </x:slot>

        <x-slot name="subtitle">
            @if ($accessUser->label)
                <span class="tooltip" data-tip="{{ __('Username') }}">
                    <span class="font-mono">{{ $accessUser->username }}</span>
                </span>
                
                <span class="mx-1">&CenterDot;</span>
            @endif

            <span class="tooltip" data-tip="{{ __('Created') }}">{{ $accessUser->created_at }}</span>
        </x-slot>

        <x-slot name="suffix">
            @if (!$accessUser->active)
                <span class="tooltip" data-tip="{{ __('Inactive') }}">
                    <i class="fa-solid fa-circle-exclamation text-error text-xl"></i>
                </span>
            @endif

            @if ($accessUser->readonly)
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
                    <a href="{{ route('access-users.edit', ['access_user' => $accessUser->username]) }}">
                        <i class="fas fa-edit mr-2"></i>
                        {{ __('Edit access user') }}
                    </a>
                </li>

                <form
                    method="POST"
                    action="{{ route('access-users.destroy', ['access_user' => $accessUser->username]) }}"
                    onsubmit="return confirm('{{ __('Are you sure you want to delete this access user?') }}')"
                >
                    @method('DELETE')
                    @csrf
                    
                    <li>
                        <button class="hover:bg-error hover:text-error-content">
                            <i class="fas fa-trash mr-2"></i>
                            {{ __('Delete access user') }}
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

                <button
                    class="btn btn-circle btn-sm"
                    onclick="
                        navigator.clipboard.writeText('{{ str_replace('\'', '\\\'', session('generated-password')) }}');
                        changeClass(this.getElementsByTagName('i')[0], 'fa-solid fa-check text-success', 1000)
                    "
                >
                    <i class="fa-solid fa-copy"></i>
                </button>
            </div>
        </div>
    @endif

    <x-card id="files">
        <x-slot name="title" class="mb-4">
            {{ __('Accessible files') }}
            <small class="font-normal">({{ $accessUser->files->count() }})</small>
        </x-slot>

        <div class="overflow-auto w-full bg-base-100 rounded-md">
            <x-files-table.table :filesCount="count($accessUser->files)" :showCountSummary="false">
                @foreach ($accessUser->files as $file)
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