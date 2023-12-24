@props([
    'iconClass' => null,
])

<div class="flex gap-4 items-center px-4 sm:px-0">
    @if ($iconClass !== null)
        <i class="{{ $iconClass }} text-3xl"></i>
    @elseif (isset($icon))
        {{ $icon }}
    @endif

    <div>
        <h2 {{ $title->attributes->merge(['class' => 'mb-0 text-lg leading-none flex gap-2 items-center']) }}>
            {{ $title }}
        </h2>

        @isset ($subtitle)
            <span class="text-sm text-base-content/60">
                {{ $subtitle }}
            </span>
        @endisset
    </div>

    @isset ($suffix)
        {{ $suffix }}
    @endisset
</div>