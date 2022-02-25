@props(['tooltip'])

<div
    {{ $attributes->merge([
        'class' => 'relative'
    ]) }}
    x-data="{'showTooltip': false}"
>
    <span @mouseover="showTooltip = true" @mouseleave="showTooltip = false">{{ $slot }}</span>
    
    <div
        x-cloak
        x-show="showTooltip"
        class="absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full whitespace-nowrap px-2 py-1 rounded-md shadow bg-white dark:bg-gray-800"
    >
        {{ $tooltip }}
    </div>
</div>