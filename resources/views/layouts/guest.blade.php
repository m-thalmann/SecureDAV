<x-html-skeleton :title="$title ?? null" class="flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
    <div>
        <a href="/">
            <img src="{{ asset('images/icon.png') }}" alt="SecureDAV Icon" class="w-20 h-20 fill-current text-gray-500" />
        </a>
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
        {{ $slot }}
    </div>
</x-html-skeleton>