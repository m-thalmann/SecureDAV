@props([
    'directories' => null,
    'file' => null,
    'directoryRoute' => fn(?\App\Models\Directory $directory) => route('browse.index', ['directory' => $directory?->uuid]),
    'fileRoute' => fn(\App\Models\File $file) => route('files.show', ['file' => $file->uuid]),
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
            <a href="{{ $directoryRoute(null) }}" class="!no-underline"><i class="fas fa-home"></i></a>
        </li>

        @foreach ($directories ?? [] as $breadcrumb)
            <li>
                <a href="{{ $directoryRoute($breadcrumb) }}" class="!inline-block max-w-[16ch] overflow-hidden text-ellipsis">{{ $breadcrumb->name }}</a>
            </li>
        @endforeach

        @if ($file)
            <li>
                <a href="{{ $fileRoute($file) }}" class="flex items-center gap-2">
                    <i class="{{ $file->fileIcon }}"></i> {{ $file->fileName }}
                </a>
            </li>
        @endif

        {{ $slot }}
    </ul>
</div>