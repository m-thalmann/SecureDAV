<x-html-skeleton :title="$title ?? null">
    @if (isset($header))
        <header class="bg-base-300 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endif

    <main>
        {{ $slot }}
    </main>
</x-html-skeleton>
