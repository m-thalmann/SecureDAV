@props([
    'file' => null,
    'link' => null,
])

<li>
    <div class="flex !cursor-default">
        <a href="{{ route('files.show', [$file]) }}" target="_blank" rel="noreferrer noopener" class="btn btn-xs btn-circle">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </a>

        @if ($link !== null)
            <a href="{{ $link }}" class="flex-1">
        @else
            <span class="flex-1">
        @endif
            <span class="w-[3ch] inline-block text-center">
                <i class="{{ $file->fileIcon }}"></i>
            </span>

            {{ $file->name }}

            @isset($suffix)
                {{ $suffix }}
            @endisset
        @if ($link !== null)
            </a>
        @else
            </span>
        @endif
        
        @isset($action)
            {{ $action }}
        @endisset
    </div>
</li>