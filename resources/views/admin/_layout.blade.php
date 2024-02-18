<x-app-layout title="{{ __('Admin area') }}">
    <x-header-title iconClass="fa-solid fa-screwdriver-wrench">
        <x:slot name="title">
            {{ __('Admin area') }}
        </x:slot>
    </x-header-title>

    <div class="flex flex-col gap-8 md:flex-row">
        <nav class="md:w-64 px-4 sm:px-0">
            <ul class="menu bg-base-300 rounded-box p-4">
                <li>
                    <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-gauge w-6"></i>
                        {{ __('Dashboard') }}
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-users w-6"></i>
                        {{ __('Users') }}
                    </a>
                </li>
            </ul>
        </nav>

        <div class="flex-1 min-w-0">
            @yield('content')
        </div>
    </div>
</x-app-layout>
