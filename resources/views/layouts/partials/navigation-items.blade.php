@php
    $navigationItems = [
        [
            "route" => "browse.index",
            "name" => __("Files"),
            "icon" => "fa-solid fa-folder"
        ],
        [
            "route" => "access.index",
            "name" => __("Access"),
            "icon" => "fa-solid fa-shield-alt"
        ],
        [
            "route" => "backups.index",
            "name" => __("Backups"),
            "icon" => "fa-solid fa-sync-alt"
        ],
    ];
@endphp

@foreach ($navigationItems as $item)
    <li class="h-full">
        <a
            href="{{ route($item['route']) }}"
            @class([
                'flex items-center gap-2',
                'bg-primary text-primary-content' => request()->routeIs($item['route'])
            ])
        >
            <i class="{{ $item['icon'] }} mr-2"></i> {{ $item['name'] }}
        </a>
    </li>
@endforeach