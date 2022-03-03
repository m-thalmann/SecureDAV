@php
    $navigationItems = [
        [
            "route" => "dashboard",
            "name" => __("Dashboard"),
            "icon" => "fa-solid fa-house"
        ],
        [
            "route" => "files",
            "name" => __("Files"),
            "icon" => "fa-solid fa-folder"
        ],
        [
            "route" => "access",
            "name" => __("Access"),
            "icon" => "fa-solid fa-shield-alt"
        ],
        [
            "route" => "backups",
            "name" => __("Backups"),
            "icon" => "fa-solid fa-sync-alt"
        ],
    ];
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 dark:bg-gray-900 dark:border-black sticky top-0 z-50">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-10 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    @foreach ($navigationItems as $item)
                        <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['route'])">
                            <i class="{{ $item['icon'] }} mr-2"></i> {{ $item['name'] }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none
                                 focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out dark:text-gray-300 dark:hover:text-gray-400 dark:focus:text-gray-500"
                        >
                            <div class="rounded-full p-2 w-9 h-9 bg-orange-500 text-white">
                                {{ generateNameInitials(Auth::user()->name) }}
                            </div>

                            <span class="ml-2 hidden lg:inline-block">
                                {{ Auth::user()->name }}
                            </span>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('settings')">
                            <i class="fa-solid fa-gear mr-2"></i> {{ __('Settings') }}
                        </x-dropdown-link>

                        <div class="flex text-center my-2">
                            <div
                                class="dark-theme-button py-2 inline-block w-1/3 transition hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer"
                                onclick="switchTheme('dark')"
                            >
                                <i class="fa-solid fa-moon"></i>
                            </div>
                            <div
                                class="light-theme-button py-2 inline-block w-1/3 transition hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer"
                                onclick="switchTheme('light')"
                            >
                                <i class="fa-solid fa-sun"></i>
                            </div>
                            <div
                                class="system-theme-button py-2 inline-block w-1/3 transition hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer"
                                onclick="switchTheme(null)"
                            >
                                <i class="fa-solid fa-desktop"></i>
                            </div>
                        </div>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                                class="hover:bg-red-500 hover:text-white focus:bg-red-700 focus:text-white
                                     dark:hover:bg-red-600 dark:focus:bg-red-700"
                            >
                                <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button
                    @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100
                         focus:text-gray-500 transition duration-150 ease-in-out dark:hover:bg-gray-800 dark:hover:text-white dark:focus:bg-gray-700 dark:focus:text-white"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @foreach ($navigationItems as $item)
                <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['route'])">
                    <i class="{{ $item['icon'] }} mr-2"></i> {{ $item['name'] }}
                </x-responsive-nav-link>
            @endforeach
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4 flex items-center gap-4">
                <div class="rounded-full p-2 w-9 h-9 bg-orange-500 text-white text-sm inline-block">
                    {{ generateNameInitials(Auth::user()->name) }}
                </div>

                <div class="inline-block">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-300">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-400 dark:text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('settings')">
                    <i class="fa-solid fa-gear mr-2"></i> {{ __('Settings') }}
                </x-responsive-nav-link>

                <div class="flex text-center">
                    <div
                        class="dark-theme-button py-2 inline-block w-1/3 transition hover:bg-gray-200 dark:hover:bg-gray-800 cursor-pointer"
                        onclick="switchTheme('dark')"
                    >
                        <i class="fa-solid fa-moon"></i>
                    </div>
                    <div
                        class="light-theme-button py-2 inline-block w-1/3 transition hover:bg-gray-200 dark:hover:bg-gray-800 cursor-pointer"
                        onclick="switchTheme('light')"
                    >
                        <i class="fa-solid fa-sun"></i>
                    </div>
                    <div
                        class="system-theme-button py-2 inline-block w-1/3 transition hover:bg-gray-200 dark:hover:bg-gray-800 cursor-pointer"
                        onclick="switchTheme(null)"
                    >
                        <i class="fa-solid fa-desktop"></i>
                    </div>
                </div>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link
                        :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="hover:border-red-700 dark:hover:border-red-900"
                    >
                        <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
