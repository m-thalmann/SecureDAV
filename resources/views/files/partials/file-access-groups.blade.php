<x-card id="access-groups">
    <x-slot name="title" :amount="$file->accessGroups->count()" class="mb-4">
        {{ __('Access groups') }}
    </x-slot>

    <div class="actions flex gap-4 items-center mb-4">
        <a href="{{ route('access-groups.index') }}" class="btn btn-neutral btn-sm">
            <i class="fa-solid fa-arrow-right mr-2"></i>
            {{ __('Browse access groups') }}
        </a>
    </div>

    @if ($file->accessGroups->count() > 0)
        <div class="overflow-auto w-full bg-base-100 rounded-md max-h-[25em]">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Label') }}</th>
                        <th class="text-right">{{ __('Users') }}</th>
                        <th class="text-center">{{ __('Read-Only') }}</th>
                        <th class="text-center">{{ __('Active') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($file->accessGroups as $accessGroup)
                        <tr>
                            <td>
                                <a href="{{ route('access-groups.show', ['access_group' => $accessGroup->uuid]) }}" class="link">{{ $accessGroup->label }}</a>
                            </td>
                            <td class="text-right">{{ $accessGroup->users_count }}</td>
                            <td class="text-center">
                                <input type="checkbox" @checked($accessGroup->readonly) class="checkbox checkbox-primary cursor-not-allowed align-middle" tabindex="-1" onclick="return false;" />
                            </td>
                            <td class="text-center">
                                @if ($accessGroup->active)
                                    <i class="fa-solid fa-circle-check text-success text-xl"></i>
                                @else
                                    <i class="fa-solid fa-circle-xmark text-error text-xl"></i>
                                @endif
                            </td>
                            <td>
                                <span class="tooltip" data-tip="{{ $accessGroup->created_at }}">{{ $accessGroup->created_at->diffForHumans() }}</span>
                            </td>
                            <td>
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
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <span class="italic text-base-content/70">{{ __('This file is not accessible by any access group') }}</span>
    @endif
</x-card>