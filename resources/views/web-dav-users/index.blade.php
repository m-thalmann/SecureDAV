<x-app-layout :title="__('WebDav Users')">
    <x-card>
        <x-slot name="title" icon="fa-solid fa-user-group" :amount="$webDavUsers->total()">
            {{ __('WebDav Users') }}
        </x-slot>

        <div class="actions my-4 flex flex-col gap-4">
            <a href="{{ route('web-dav-users.create') }}" class="btn btn-neutral btn-sm w-fit">
                <i class="fa-solid fa-user-plus mr-2"></i>
                {{ __('Create WebDav user') }}
            </a>
        </div>

        <form method="GET" class="w-full sm:w-1/2 mb-4 relative">
            <label for="search-input" class="absolute top-1/2 left-4 -translate-y-1/2">
                <i class="fa-solid fa-search"></i>
            </label>

            <input value="{{ $search }}" placeholder="{{ __('Search users...') }}" name="q" class="input bg-base-300 w-full pl-10 max-sm:rounded-none shadow-md" id="search-input" />
        </form>

        <div class="overflow-auto w-full bg-base-100 rounded-md">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-center">{{ __('Active') }}</th>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Username') }}</th>
                        <th class="text-right">{{ __('Accessible files') }}</th>
                        <th class="text-center">{{ __('Read-Only') }}</th>
                        <th>{{ __('Last access') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($webDavUsers as $webDavUser)
                        <tr>
                            <td class="text-center">
                                @if ($webDavUser->active)
                                    <i class="fa-solid fa-circle-check text-success text-xl"></i>
                                @else
                                    <i class="fa-solid fa-circle-xmark text-error text-xl"></i>
                                @endif
                            </td>
                            <td @class([
                                'opacity-50' => !$webDavUser->active,
                            ])>
                                <a href="{{ route('web-dav-users.show', [$webDavUser]) }}" class="link underline-offset-2">{{ $webDavUser->label }}</a>
                            </td>
                            <td @class([
                                'font-mono',
                                'opacity-50' => !$webDavUser->active,
                            ])>
                                <span class="select-all">{{ $webDavUser->username }}</span>

                                <x-copy-button :data="$webDavUser->username" />
                            </td>
                            <td @class([
                                'text-right',
                                'opacity-50' => !$webDavUser->active,
                            ])>{{ $webDavUser->files_count }}</td>
                            <td @class([
                                'text-center',
                                'opacity-50' => !$webDavUser->active,
                            ])>
                                <input type="checkbox" @checked($webDavUser->readonly) class="checkbox checkbox-primary cursor-not-allowed align-middle" tabindex="-1" onclick="return false;" />
                            </td>
                            <td @class([
                                'opacity-50' => !$webDavUser->active,
                            ])>
                                <span class="tooltip" data-tip="{{ $webDavUser->last_access ?? '-' }}">{{ $webDavUser->last_access?->diffForHumans() ?? '-' }}</span>
                            </td>
                            <td>
                                <x-dropdown
                                    :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                    width="w-36"
                                >
                                    <li>
                                        <a href="{{ route('web-dav-users.edit', [$webDavUser]) }}">
                                            <i class="fas fa-edit w-6"></i>
                                            {{ __('Edit') }}
                                        </a>
                                    </li>

                                    <form
                                        method="POST"
                                        action="{{ route('web-dav-users.destroy', [$webDavUser]) }}"
                                        onsubmit="return confirm(`{{ __('Are you sure you want to delete this WebDav user?') }}`)"
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

                    @if (count($webDavUsers) === 0)
                        <tr>
                            <td colspan="7" class="text-center italic text-base-content/70">{{ __('No WebDav users') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-card>

    {{ $webDavUsers->links() }}
</x-app-layout>