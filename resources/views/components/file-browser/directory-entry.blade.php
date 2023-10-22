@props([
    'directory' => null,
    'link' => "?directory={$directory->uuid}",
])

<li>
    <div class="flex !cursor-default">
        <a href="{{ route('browse.index', ['directory' => $directory->uuid]) }}" target="_blank" rel="noreferrer noopener" class="btn btn-xs btn-circle">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </a>

        <a href="{{ $link }}" class="flex-1">
            <span class="w-[3ch] inline-block text-center">
                <i class="fas fa-folder text-secondary"></i>
            </span>
            {{ $directory->name }}
        </a>

        @isset($action)
            {{ $action }}
        @endisset
    </div>
</li>