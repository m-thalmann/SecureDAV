<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="Secure webdav file storage">

        <title>
            @if($title !== null)
                {{ $title }} -
            @endif

            {{ config('app.name') }}
        </title>

        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased {{ $bodyClass ?? "" }}">
        @yield('htmlBody')

        @if (session('snackbar'))
            <div
                class="toast m-6"
                @if (session('snackbar')->duration !== null)
                    x-init="setTimeout(function() { $el.remove() }, {{ session('snackbar')->duration * 1000 }})"
                @endif
            >
                <x-session-message :message="session('snackbar')" />
            </div>
        @endif

        @stack('scripts')
    </body>
</html>

