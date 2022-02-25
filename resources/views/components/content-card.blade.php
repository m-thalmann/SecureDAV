@props(['title' => null, 'maxWidth' => '7xl', 'grid' => false, 'href' => null])

<div {{ $attributes->merge(["class" => $grid ? "" : "max-w-$maxWidth sm:px-6 lg:px-8 mx-auto"]) }}>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800 relative @if($href !== null) cursor-pointer hover:shadow-xl block transition @endif">
        @if($href !== null)
            <a href="{{ $href }}" class="absolute top-0 left-0 right-0 bottom-0 z-0"></a>
        @endif

        <div class="content-card-content p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-none relative @if($href !== null) z-10 pointer-events-none @endif">
            @if($title !== null)
                <h3 class="font-semibold text-xl text-gray-800 leading-tight mb-4 dark:text-gray-300">{{ $title }}</h3>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>