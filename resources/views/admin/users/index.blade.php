@extends('admin._layout')

@section('content')
    @if (session('generated-password'))
        <div class="alert max-sm:rounded-none md:w-fit !mt-1">
            <i class="fa-solid fa-key text-success"></i>
            <span>
                {{ __('Password of user reset') }}: <span class="font-mono ml-2 inline-block blur" id="generated-password">{{ session('generated-password') }}</span>
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

    <x-card>
        <x-slot name="title" icon="fa-solid fa-user-group" :amount="$users->total()">
            {{ __('Manage users') }}
        </x-slot>

        <div class="actions my-4 flex flex-col gap-4">
            <a href="{{ route('admin.users.create') }}" class="btn btn-neutral btn-sm w-fit">
                <i class="fa-solid fa-user-plus mr-2"></i>
                {{ __('Create user') }}
            </a>
        </div>

        <form method="GET" class="w-full sm:w-1/2 mb-4 relative">
            <label for="search-input" class="absolute top-1/2 left-4 -translate-y-1/2">
                <i class="fa-solid fa-search"></i>
            </label>

            <input value="{{ $search }}" placeholder="{{ __('Search users...') }}" name="q" class="input bg-base-300 w-full pl-10 shadow-md" id="search-input" />
        </form>

        <div class="overflow-auto w-full bg-base-100 rounded-md">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Files') }}</th>
                        <th>{{ __('WebDav users') }}</th>
                        <th>{{ __('Configured backups') }}</th>
                        <th class="w-0">{{ __('Admin') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                {{ $user->email }}
                            </td>
                            <td>
                                {{ $user->name }}
                            </td>
                            <td>
                                {{ $user->files_count }}
                            </td>
                            <td>
                                {{ $user->web_dav_users_count }}
                            </td>
                            <td>
                                {{ $user->backup_configurations_count }}
                            </td>
                            <td class="text-center">
                                @if ($user->is_admin)
                                    <i class="fa-solid fa-shield-halved text-success text-xl"></i>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($user->id !== auth()->id())
                                    <x-dropdown
                                        :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                        width="w-52"
                                    >
                                        <li>
                                            <a href="{{ route('admin.users.edit', [$user]) }}">
                                                <i class="fas fa-edit w-6"></i>
                                                {{ __('Edit') }}
                                            </a>
                                        </li>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', [$user]) }}"
                                            onsubmit="return confirm(`{{ __('Are you sure you want to delete this user with all of it\'s files?') }}`)"
                                        >
                                            @method('DELETE')
                                            @csrf

                                            <li>
                                                <button class="hover:bg-error hover:text-error-content">
                                                    <i class="fa-solid fa-trash w-6"></i>
                                                    {{ __('Permanently delete') }}
                                                </button>
                                            </li>
                                        </form>
                                    </x-dropdown>
                                @else
                                    <i class="fa-solid fa-ban text-lg text-error"></i>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    {{ $users->links() }}
@endsection
