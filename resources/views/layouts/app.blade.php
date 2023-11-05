@extends('layouts._base', ['title' => $title])

@section('htmlBody')
    <div class="drawer h-full">
        <input id="navigation-drawer" type="checkbox" class="drawer-toggle" /> 
        <div class="drawer-content flex flex-col overflow-hidden h-full">
            @include('layouts.partials.app-header')
            
            @if ($user->is_webdav_suspended)
                <span class="bg-warning text-warning-content py-1 px-2 flex items-center justify-center gap-2 text-sm">
                    <i class="fa-solid fa-power-off"></i>
                    {{ __('You have suspended your account\'s WebDAV. No files can be viewed or updated.') }}
                </span>
            @endif

            <div class="h-full overflow-auto py-12 sm:px-6 lg:px-8">
                <main {{ $attributes->merge(['class' => 'w-full max-w-7xl mx-auto space-y-6']) }}>
                    {{ $slot }}
                </main>
            </div>
        </div> 

        {{-- Mobile drawer --}}
        <div class="drawer-side z-50 lg:hidden">
            <label for="navigation-drawer" class="drawer-overlay"></label> 

            <div class="p-2 w-80 min-h-full bg-base-300">
                <div class="top-drawer-row flex items-center gap-2 mb-4">
                    <label for="navigation-drawer" class="btn btn-square btn-ghost">
                        <i class="fa-solid fa-bars"></i>
                    </label>

                    <h1 class="flex items-center gap-2 text-xl">
                        <img src="{{ asset('images/icon.png') }}" alt="SecureDAV Icon" class="h-10 w-auto" />
                        <span class="lg:sr-only">{{ config('app.name') }}</span>
                    </h1>
                </div>

                <ul class="menu">
                    @include('layouts.partials.navigation-items')
                </ul>
            </div>
        </div>
    </div>
@endsection