<x-card id="users">
    <x-slot name="title" :amount="$accessGroup->users->count()">
        {{ __('Group users') }}
    </x-slot>

    <div class="actions my-4">
        <a href="{{ route('access-groups.access-group-users.create', [$accessGroup]) }}" class="btn btn-neutral btn-sm">
            <i class="fa-solid fa-user-plus mr-2"></i>
            {{ __('Create group user') }}
        </a>
    </div>

    <div class="overflow-auto w-full bg-base-100 rounded-md min-h-[12em] max-h-[25em]">
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
                                    <a href="{{ route('access-group-users.edit', [$accessGroupUser]) }}">
                                        <i class="fas fa-edit mr-2"></i>
                                        {{ __('Edit group user') }}
                                    </a>
                                </li>

                                <form
                                    method="POST"
                                    action="{{ route('access-group-users.reset-password', [$accessGroupUser]) }}"
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
                                    action="{{ route('access-group-users.destroy', [$accessGroupUser]) }}"
                                    onsubmit="return confirm(`{{ __('Are you sure you want to delete this group user?') }}`)"
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