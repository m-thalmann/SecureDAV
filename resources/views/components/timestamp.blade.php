@props([
    'timestamp' => null,
    'fallback' => '-',
    'tooltipFallback' => null,
    'innerContainerClass' => null,
])

@php
    $content = $timestamp?->diffForHumans() ?? $fallback;
@endphp

<span {{ $attributes->merge(['class' => 'tooltip']) }} data-tip="{{ $timestamp ?? $tooltipFallback ?? $fallback }}">
    @if ($innerContainerClass)
        <span class="{{ $innerContainerClass }}">{{ $content }}</span>
    @else
        {{ $content }}
    @endif
</span>