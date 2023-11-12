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

            <div class="flex gap-2">
                <span class="tooltip" data-tip="{{ __('Read') }}">
                    <i class="fa-solid fa-eye text-secondary text-xl"></i>
                </span>
    
                @if (!$accessGroup->readonly)
                    <span class="tooltip" data-tip="{{ __('Write') }}">
                        <i class="fa-solid fa-file-pen text-secondary text-xl"></i>
                    </span>
                @endif
            </div>

            <span class="flex-1"></span>

            <x-dropdown align="end" width="w-36">
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

    @include('access-groups.partials.access-group-users')

    @include('access-groups.partials.access-group-files')
</x-app-layout>