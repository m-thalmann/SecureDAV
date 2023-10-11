@props([
    'icon' => null,
    'buttonClass' => 'btn btn-sm btn-circle',
    'position' => 'bottom',
    'align' => 'start',
    'positionAligned' => null,
    'width' => 'w-48',
])

@php
    if ($positionAligned !== null) {
        [$position, $align] = explode('-', $positionAligned, 2);
    }

    $dropdownPositionClass = match ($position) {
        'left' => 'dropdown-left',
        'right' => 'dropdown-right',
        'top' => 'dropdown-top',
        default => 'dropdown-bottom',
    };

    $listPositionClass = match ($position) {
        'left' => 'mr-1',
        'right' => 'ml-1',
        'top' => 'mb-1',
        default => 'mt-1',
    };
@endphp

<div @class([
    'dropdown',
    $align === 'start' ? 'dropdown-start' : 'dropdown-end',
    $dropdownPositionClass,
])>
    <label tabindex="0" class="{{ $buttonClass }}">
        @if($icon !== null)
            {{ $icon }}
        @else
            <i class="fa-solid fa-ellipsis"></i>
        @endif
    </label>
    <ul
        tabindex="0"
        @class([
            'dropdown-content z-[1] menu p-2 shadow bg-base-300 rounded-box',
            $width,
            $listPositionClass,
        ])
    >
        {{ $slot }}
    </ul>
</div>