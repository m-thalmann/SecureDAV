<x-app-layout :title="__('Access')">
    <x-card>
        <x-slot name="title">
            <i class="fa-solid fa-shield-alt mr-2"></i>
            {{ __('Access users') }}
            <small class="font-normal">({{ count($accessUsers) }})</small>
        </x-slot>

        <div class="actions my-4">
            <a href="{{ route('access-users.create') }}" class="btn btn-neutral btn-sm">
                <i class="fa-solid fa-user-plus mr-2"></i>
                {{ __('Create access user') }}
            </a>
        </div>

        <div class="overflow-auto w-full bg-base-100 rounded-md">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Username') }}</th>
                        <th>{{ __('Label') }}</th>
                        <th class="text-right">{{ __('Accessible files') }}</th>
                        <th class="text-center">{{ __('Readonly') }}</th>
                        <th class="text-center">{{ __('Active') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accessUsers as $accessUser)
                        <tr>
                            <td>
                                <a href="{{ route('access-users.show', ['access_user' => $accessUser->username]) }}" class="link">{{ $accessUser->username }}</a>
                            </td>
                            <td>{{ $accessUser->label ?? '-' }}</td>
                            <td class="text-right">{{ $accessUser->files_count }}</td>
                            <td class="text-center">
                                <input type="checkbox" @checked($accessUser->readonly) class="checkbox checkbox-primary cursor-not-allowed" onclick="return false;" />
                            </td>
                            <td class="text-center">
                                <input type="checkbox" @checked($accessUser->active) class="checkbox checkbox-primary cursor-not-allowed" onclick="return false;" />
                            </td>
                            <td>
                                <span class="tooltip" data-tip="{{ $accessUser->created_at }}">{{ $accessUser->created_at->diffForHumans() }}</span>
                            </td>
                            <td>
                                <x-dropdown
                                    :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                    width="w-56"
                                >
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
                            </td>
                        </tr>
                    @endforeach

                    @if (count($accessUsers) === 0)
                        <tr>
                            <td colspan="7" class="text-center italic text-base-content/70">{{ __('No access users') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>