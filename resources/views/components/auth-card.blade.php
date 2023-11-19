@props([
    'subtitle' => null,
])

<div class="flex flex-col items-center gap-2">
    <img src="{{ asset('images/icon.png') }}" alt="SecureDAV Icon" class="w-20 h-20 fill-current text-gray-500" />
    <h1 class="text-3xl">{{ config('app.name') }}</h1>

    @if ($subtitle)
        <h2 class="text-base-content/50">{{ $subtitle }}</h2>
    @endif
</div>

<div class="sm:card sm:bg-base-200 sm:shadow-xl sm:max-w-md w-full mt-6">
    <div class="card-body items-center text-center">
        {{ $slot }}
    </div>
</div>