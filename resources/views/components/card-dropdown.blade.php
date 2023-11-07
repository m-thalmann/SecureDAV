@props([
    'icon' => null,
])

<div class="dropdown dropdown-end">
    <label tabindex="0" class="btn btn-circle btn-ghost btn-xs text-info">
        @if($icon !== null)
            {{ $icon }}
        @else
            <i class="fa-solid fa-circle-info text-info"></i>
        @endif
    </label>
    <div tabindex="0" class="card compact dropdown-content z-[1] shadow bg-base-300 rounded-box w-64">
        <div class="card-body">
            {{ $slot }}
        </div>
    </div>
</div>