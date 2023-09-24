@props([
    'name' => null,
    'errorBag' => 'default'
])

<div {{ $attributes->merge(['class' => 'form-control']) }}>
    <label class="label" for="{{ $name }}">
        <span class="label-text">{{ $label }}</span>
    </label>
    {{ $slot }}
    <label class="label">
        <span class="label-text-alt">
            <x-input-error :messages="$errors->getBag($errorBag)->get($name)" />
        </span>
    </label>
</div>