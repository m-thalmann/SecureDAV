@php
    $navigationItems = [
        [
            "route" => "browse.index",
            "name" => __("Files"),
            "icon" => "fa-solid fa-folder",
            "activeRoutes" => [
                "browse.index",
                "files.*"
            ],
        ],
        [
            "route" => "access-users.index",
            "name" => __("Access"),
            "icon" => "fa-solid fa-shield-alt",
            "activeRoutes" => [
                "access-users.*",
            ],
        ],
        [
            "route" => "backups.index",
            "name" => __("Backups"),
            "icon" => "fa-solid fa-sync-alt",
            "activeRoutes" => [
                "backups.index",
            ],
        ],
    ];
@endphp

@foreach ($navigationItems as $item)
    <li class="h-full">
        <a
            href="{{ route($item['route']) }}"
            @class([
                'flex items-center gap-2',
                'bg-primary text-primary-content' => count(array_filter($item['activeRoutes'], fn($route) => request()->routeIs($route))) > 0,
            ])
        >
            <i class="{{ $item['icon'] }} mr-2"></i> {{ $item['name'] }}
        </a>
    </li>
@endforeach