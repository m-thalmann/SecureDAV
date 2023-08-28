<div class="flex flex-col items-center gap-2">
    <img src="{{ asset('images/icon.png') }}" alt="SecureDAV Icon" class="w-20 h-20 fill-current text-gray-500" />
    <h1 class="text-3xl">{{ config('app.name') }}</h1>
</div>

<div class="card bg-base-200 shadow-xl mt-6 w-full sm:max-w-md max-sm:rounded-none">
    <div class="card-body items-center text-center">
        {{ $slot }}
    </div>
</div>