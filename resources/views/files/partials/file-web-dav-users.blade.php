<x-card id="web-dav-users">
    <x-slot name="title" :amount="$file->webDavUsers->count()" class="mb-4">
        {{ __('WebDav Users') }}
    </x-slot>

    <div class="actions flex gap-4 items-center mb-4">
        <a href="{{ route('web-dav-users.index') }}" class="btn btn-neutral btn-sm">
            <i class="fa-solid fa-arrow-right mr-2"></i>
            {{ __('Browse WebDav users') }}
        </a>
    </div>

    @if ($file->webDavUsers->count() > 0)
        <div class="overflow-auto w-full bg-base-100 rounded-md max-h-[25em]">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Username') }}</th>
                        <th class="text-center">{{ __('Read-Only') }}</th>
                        <th class="text-center">{{ __('Active') }}</th>
                        <th>{{ __('Last access') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($file->webDavUsers as $webDavUser)
                        <tr>
                            <td>
                                <a href="{{ route('web-dav-users.show', [$webDavUser]) }}" class="link">{{ $webDavUser->label }}</a>
                            </td>
                            <td class="font-mono">
                                <span class="select-all">{{ $webDavUser->username }}</span>

                                <x-copy-button :data="$webDavUser->username" />
                            </td>
                            <td class="text-center">
                                <input type="checkbox" @checked($webDavUser->readonly) class="checkbox checkbox-primary cursor-not-allowed align-middle" tabindex="-1" onclick="return false;" />
                            </td>
                            <td class="text-center">
                                @if ($webDavUser->active)
                                    <i class="fa-solid fa-circle-check text-success text-xl"></i>
                                @else
                                    <i class="fa-solid fa-circle-xmark text-error text-xl"></i>
                                @endif
                            </td>
                            <td>
                                <span class="tooltip" data-tip="{{ $webDavUser->last_access ?? '-' }}">{{ $webDavUser->last_access?->diffForHumans() ?? '-' }}</span>
                            </td>
                            <td>
                                <x-dropdown :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)">
                                    <li>
                                        <button class="hover:bg-error hover:text-error-content">
                                            <i class="fa-solid fa-user-slash w-6"></i>
                                            {{ __('Revoke access') }}
                                        </button>
                                    </li>
                                </x-dropdown>
                            </td>
                        </tr>
                    @endforeach

                    @if (count($file->webDavUsers) === 0)
                        <tr>
                            <td colspan="7" class="text-center italic text-base-content/70">{{ __('No WebDav users with access') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @else
        <span class="italic text-base-content/70">{{ __('This file is not accessible by any access group') }}</span>
    @endif
</x-card>