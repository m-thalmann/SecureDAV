@props([
    'file' => null,
    'link' => null,
])

<li>
    <div class="flex !cursor-default">
        <a href="{{ route('files.show', ['file' => $file->uuid]) }}" target="_blank" rel="noreferrer noopener" class="btn btn-xs btn-circle">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </a>

        <a href="{{ $link }}" @class([
            'flex-1',
            'pointer-events-none' => $link === null
        ])>
            <span class="w-[3ch] inline-block text-center">
                <i class="{{ $file->fileIcon }}"></i>
            </span>
            {{ $file->name }}
        </a>
        
        @isset($action)
            {{ $action }}
        @endisset
    </div>
</li>