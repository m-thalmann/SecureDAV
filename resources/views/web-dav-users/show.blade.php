<x-app-layout :title="$webDavUser->label . ' - ' . __('WebDav User')">
    <x-header-title iconClass="fa-solid fa-user">
        <x:slot name="title">
            {{ $webDavUser->label }}
        </x:slot>

        <x-slot name="subtitle">
            <span class="tooltip" data-tip="{{ __('Created') }}">{{ $webDavUser->created_at }}</span>
        </x-slot>

        <x-slot name="suffix">
            @if ($webDavUser->active)
                <span class="tooltip" data-tip="{{ __('Active') }}">
                    <i class="fa-solid fa-circle-check text-success text-xl"></i>
                </span>
            @else
                <span class="tooltip" data-tip="{{ __('Inactive') }}">
                    <i class="fa-solid fa-circle-xmark text-error text-xl"></i>
                </span>
            @endif

            <div class="flex gap-2">
                <span class="tooltip" data-tip="{{ __('Read') }}">
                    <i class="fa-solid fa-eye text-secondary text-xl"></i>
                </span>
    
                @if (!$webDavUser->readonly)
                    <span class="tooltip" data-tip="{{ __('Write') }}">
                        <i class="fa-solid fa-file-pen text-secondary text-xl"></i>
                    </span>
                @endif
            </div>

            <span class="flex-1"></span>

            <x-dropdown align="end" width="w-36">
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
        </x-slot>
    </x-header-title>

    <x-form-field name="username" class="w-full md:w-2/3 lg:w-1/2">
        <x-slot name="label">{{ __('Username') }}</x-slot>

        <div class="flex gap-2 items-center">
            <x-input name="username" class="input-sm bg-base-200" :value="$webDavUser->username" readonly />
    
            <x-copy-button inputId="username" plain class="btn btn-sm btn-neutral" />
        </div>
    </x-form-field>

    @include('web-dav-users.partials.web-dav-user-files')
</x-app-layout>