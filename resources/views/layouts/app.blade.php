@props([
    'header' => ['icon' => 'fa-solid fa-question', 'items' => [ 'undefined' ]],
    'title' => null
])

<!DOCTYPE html>
<html lang="en" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Secure Webdav file storage">

        <title>
            @if($title !== null)
                {{ $title }} -        
            @endif
            {{ config('app.name', 'Laravel') }}
        </title>

        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">

        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">

        <script src="{{ mix('js/app.js') }}" defer></script>
        <script src="{{ mix('js/boot.js') }}"></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
            @include('layouts.navigation')

            <header class="bg-white shadow dark:bg-gray-900 mb-6">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-300">
                        <i class="{{ $header['icon'] }} mr-3"></i>
                        <?php
                        $amountBreadCrumbs = count($header["items"]);

                        for ($i = 0; $i < $amountBreadCrumbs; $i++) {
                            $item = $header["items"][$i];

                            if (is_string($item)) { ?>
                                <span class="{{ $i + 1 < $amountBreadCrumbs ? "text-gray-400" : "" }}">{{ $item }}</span>
                            <?php } else {$itemName = array_keys($item)[0]; ?>
                                <a class="text-gray-500 hover:text-gray-400 dark:hover:text-gray-600" href="{{ $item[$itemName] }}">{{ $itemName }}</a>
                            <?php }

                            if ($i + 1 < $amountBreadCrumbs) { ?>
                                <span class="mx-2 opacity-20 font-thin">/</span>
                            <?php }
                        }
                        ?>
                    </h2>
                </div>
            </header>

            <main class="pb-6">
                {{ $slot }}
            </main>
        </div>

        <x-snackbar :snackbar="session()->get('snackbar', null)"></x-snackbar>
    </body>
</html>
