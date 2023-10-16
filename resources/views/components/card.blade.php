@props([
    'dialog' => false
])

<div {{ $attributes->merge(['class' => 'card bg-base-200 shadow-lg max-sm:rounded-none' . ($dialog ? ' md:w-2/3 md:mx-auto' : '')]) }}>
    <div class="card-body">
        @isset ($title)
            <h2 {{ $title->attributes->merge(['class' => 'card-title']) }}>
                {{ $title }}
            </h2>
        @endisset

        {{ $slot }}

        @isset($actions)
            <div {{ $actions->attributes->merge(['class' => 'card-actions justify-end']) }}>
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>