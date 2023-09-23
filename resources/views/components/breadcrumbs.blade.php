@props(['directories' => []])

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

        {{ $slot }}
    </ul>
</div>