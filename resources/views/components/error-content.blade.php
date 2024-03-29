<div class="w-fit max-w-lg flex flex-col overflow-auto gap-6 px-4 m-auto @auth mt-10 @endauth">
    <h2 class="text-6xl sm:text-9xl font-bold">
        {{ __('Oops!') }}
    </h2>

    <p class="text-base-content/75 text-3xl font-thin">
        {{ $description }}
    </p>

    <strong class="text-base-content/50">{{ __('Error code: :code', ['code' => $errorCode]) }}</strong>

    @if (isset($slot))
        {{ $slot }}
    @endif

    @auth
        <a href="{{ route('browse.index') }}" class="btn btn-primary">
            <i class="fa-solid fa-arrow-left"></i>
            {{ __('Go to file browser') }}
        </a>
    @endauth
</div>
