@auth
    <x-app-layout :title="$title">
        {{ $slot }}
    </x-app-layout>
@endauth
@guest
    <x-guest-layout :title="$title">
        {{ $slot }}
    </x-guest-layout>
@endguest