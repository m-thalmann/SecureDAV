@props(['title' => null])

<!DOCTYPE html>
<html lang="en">
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
    <body>
        <div class="font-sans text-gray-900 antialiased dark:bg-gray-700 dark:text-white">
            {{ $slot }}
        </div>

        <x-snackbar :snackbar="session()->get('snackbar', null)"></x-snackbar>
    </body>
</html>
