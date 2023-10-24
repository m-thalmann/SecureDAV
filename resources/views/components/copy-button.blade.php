@props([
    'icon' => null,
    'data' => '',
])

@php
    $data = str_replace('\\', '\\\\', $data);
    $data = str_replace('`', '\\`', $data);
@endphp

<button
    {{ $attributes->merge(['class' => 'btn btn-circle btn-sm btn-ghost']) }}
    onclick="
        navigator.clipboard.writeText(`{{ $data }}`);
        changeClass(this.getElementsByTagName('i')[0], 'fa-solid fa-check text-success', 1000)
    "
>
    @if($icon !== null)
        {{ $icon }}
    @else
        <i class="fa-solid fa-copy"></i>
    @endif
</button>