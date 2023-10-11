@extends('layouts._base', ['title' => $title])

@section('htmlBody')
    <div class="drawer h-full">
        <input id="navigation-drawer" type="checkbox" class="drawer-toggle" /> 
        <div class="drawer-content flex flex-col overflow-hidden h-full">
            @include('layouts.partials.app-header')
            
            <main {{ $attributes->merge(['class' => 'py-12 w-full h-full max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6']) }}>
                {{ $slot }}
            </main>
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