<x-app-layout :title="__('Access')">
    <x-card>
        <x-slot name="title" icon="fa-solid fa-user-group" :amount="count($accessGroups)">
            {{ __('Access groups') }}
        </x-slot>

        <div class="actions my-4 flex flex-col gap-4">
            <a href="{{ route('access-groups.create') }}" class="btn btn-neutral btn-sm w-fit">
                <i class="fa-solid fa-plus mr-2"></i>
                {{ __('Create access group') }}
            </a>

            <form action="{{ route('access-group-users.jump-to') }}" method="POST">
                @csrf

                <x-form-field name="username" class="w-96">
                    <x-slot name="label"><i class="fa-solid fa-user"></i> {{ __('Find access group user') }}</x-slot>
    
                    <div class="flex items-center relative">
                        <x-input name="username" placeholder="{{ __('Username') }}" required class="pr-16" />
    
                        <button class="btn btn-square btn-neutral rounded-l-none absolute right-0">
                            <i class="fa-solid fa-search"></i>
                        </button>
                    </div>
                </x-form-field>
            </form>
        </div>

        <div class="overflow-auto w-full bg-base-100 rounded-md">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Label') }}</th>
                        <th class="text-right">{{ __('Users') }}</th>
                        <th class="text-right">{{ __('Accessible files') }}</th>
                        <th class="text-center">{{ __('Read-Only') }}</th>
                        <th class="text-center">{{ __('Active') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accessGroups as $accessGroup)
                        <tr>
                            <td>
                                <a href="{{ route('access-groups.show', [$accessGroup]) }}" class="link">{{ $accessGroup->label }}</a>
                            </td>
                            <td class="text-right">{{ $accessGroup->users_count }}</td>
                            <td class="text-right">{{ $accessGroup->files_count }}</td>
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
                                <x-dropdown
                                    :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                    width="w-36"
                                >
                                    <li>
                                        <a href="{{ route('access-groups.edit', [$accessGroup]) }}">
                                            <i class="fas fa-edit w-6"></i>
                                            {{ __('Edit') }}
                                        </a>
                                    </li>

                                    <form
                                        method="POST"
                                        action="{{ route('access-groups.destroy', [$accessGroup]) }}"
                                        onsubmit="return confirm(`{{ __('Are you sure you want to delete this access group and all of it\'s users?') }}`)"
                                    >
                                        @method('DELETE')
                                        @csrf
                                        
                                        <li>
                                            <button class="hover:bg-error hover:text-error-content">
                                                <i class="fas fa-trash w-6"></i>
                                                {{ __('Delete') }}
                                            </button>
                                        </li>
                                    </form>
                                </x-dropdown>
                            </td>
                        </tr>
                    @endforeach

                    @if (count($accessGroups) === 0)
                        <tr>
                            <td colspan="7" class="text-center italic text-base-content/70">{{ __('No access groups') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>