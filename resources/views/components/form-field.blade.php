@props([
    'name' => null,
    'errorBag' => 'default'
])

<div {{ $attributes->merge(['class' => 'form-control']) }}>
    <label class="label" for="{{ $name }}">
        <span class="label-text">
            {{ $label }}

            @if ($label->attributes->has('optional'))
                <small class="font-extralight">({{ __('optional') }})</small>
            @endif
        </span>
    </label>
    {{ $slot }}
    <label class="label">
        <span class="label-text-alt">
            <x-input-error :messages="$errors->getBag($errorBag)->get($name)" />
        </span>
        
        @isset ($hint)
            <span class="label-text-alt text-right">{{ $hint }}</span>
        @endisset
    </label>
</div>