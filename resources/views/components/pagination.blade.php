@if ($paginator->hasPages())
    <div class="join w-full justify-center overflow-auto">
        {{-- Previous Page Link --}}
        <a
            href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}"
            class="join-item btn"
        >
            <i class="fa-solid fa-chevron-left"></i>
        </a>

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <button class="join-item btn btn-disabled">...</button>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <a
                        href="{{ $page == $paginator->currentPage() ? '#' : $url }}"
                        @class([
                            'join-item btn',
                            'btn-active' => $page == $paginator->currentPage(),
                        ])
                    >{{ $page }}</a>
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        <a
            href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}"
            class="join-item btn"
        >
            <i class="fa-solid fa-chevron-right"></i>
        </a>
    </div>
@endif
