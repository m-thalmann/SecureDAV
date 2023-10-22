@props([
    'breadcrumbs' => [],
    'directoryRoute' => fn(?\App\Models\Directory $directory) => "?directory={$directory?->uuid}",
])

<div {{ $attributes->merge(['class' => 'overflow-auto max-h-[25em]']) }}>
    <x-breadcrumbs
        :directories="$breadcrumbs"
        :directoryRoute="$directoryRoute"
        class="ml-2 px-0"
    />

    <ul class="menu px-0 pt-0">
        <li>
            <ul>
                {{ $slot }}
            </ul>
        </li>
    </ul>
</div>