<nav class="bg-base-300 drop-shadow-md z-40 sticky top-0">
    <div class="navbar gap-6 h-full w-full max-w-7xl mx-auto py-0 sm:px-6 lg:px-8">
        <div class="flex gap-2">
            <div class="lg:hidden">
                <label for="navigation-drawer" class="btn btn-square btn-ghost">
                    <i class="fa-solid fa-bars"></i>
                </label>
            </div> 
    
            <h1>
                <a href="{{ route('files.index') }}" class="flex items-center gap-2 text-xl sm:text-2xl">
                    <img src="{{ asset('images/icon.png') }}" alt="SecureDAV Icon" class="h-10 w-auto" />
                    <span class="lg:sr-only">{{ config('app.name') }}</span>
                </a>
            </h1>
        </div>

        <div class="flex-1">
            <ul class="menu menu-horizontal gap-2 max-lg:hidden">
                @include('layouts.partials.navigation-items')
            </ul>
        </div>

        <div class="flex-none">
            <a href="#" class="btn btn-ghost btn-circle">
                <div class="indicator">
                    <i class="fa-solid fa-bell"></i>
                    <span class="badge badge-xs badge-primary indicator-item"></span>
                </div>
            </a>
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-circle avatar placeholder">
                    <div class="bg-neutral-focus text-neutral-content rounded-full w-full">
                        <span class="text-l">{{ $user->initials }}</span>
                    </div>
                </label>
                <ul tabindex="0" class="menu dropdown-content mt-3 z-[1] p-2 shadow bg-base-200 rounded-box w-52">
                    <li>
                        <a href="{{ route('settings.index') }}">
                            <i class="fa-solid fa-gear mr-2"></i>
                            {{ __('Settings') }}
                        </a>
                    </li>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        
                        <li>
                            <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="hover:bg-error hover:text-error-content">
                                <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i>
                                {{ __('Log out') }}
                            </a>
                        </li>
                    </form>
                </ul>
            </div>
        </div>
    </div>
</nav>