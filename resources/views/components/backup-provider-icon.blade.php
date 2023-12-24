@props([
    'provider' => null,
    'configuration' => null,
    'large' => false
])

@php
    if ($configuration !== null) {
        $provider = $configuration->provider_class;
    }

    $displayInformation = $provider::getDisplayInformation();
@endphp

@if (isset($displayInformation['icon']) && $displayInformation['icon'] !== null)
    <i class="{{ $displayInformation['icon'] }} {{ $large ? ' text-3xl' : 'text-base' }}"></i>
@else
    <img src="{{ $displayInformation['iconUrl'] }}" class="{{ $large ? 'w-10 h-10' : 'w-4 h-4' }} max-w-none rounded-sm" />
@endif