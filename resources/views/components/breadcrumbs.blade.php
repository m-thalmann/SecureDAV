@props([
    'directories' => null,
    'file' => null,
])

@php
    if ($directories === null) {
        if ($file !== null) {
            $directories = $file->directory?->breadcrumbs;
        } else {
            $directories = [];
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'breadcrumbs']) }}>
    <ul>
        <li class="h-6">
            <a href="{{ route('browse.index') }}" class="!no-underline"><i class="fas fa-home"></i></a>
        </li>

        @foreach ($directories ?? [] as $breadcrumb)
            <li>
                <a href="{{ route('browse.index', ['directory' => $breadcrumb->uuid]) }}" class="!inline-block max-w-[16ch] overflow-hidden text-ellipsis">{{ $breadcrumb->name }}</a>
            </li>
        @endforeach

        @if ($file)
            <li>
                <a href="{{ route('files.show', ['file' => $file->uuid]) }}" class="flex items-center gap-2">
                    <i class="fas fa-file"></i> {{ $file->fileName }}
                </a>
            </li>
        @endif

        {{ $slot }}
    </ul>
</div>