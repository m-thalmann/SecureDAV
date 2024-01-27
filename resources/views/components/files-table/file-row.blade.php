@props([
    'file' => null,
    'hover' => true,
    'includeParentDirectory' => false,
    'deletedAtColumn' => false,
    'link' => true,
])

<tr {{ $attributes->merge(['class' => $hover ? 'hover' : '']) }}>
    <td class="text-center pr-0">
        <i class="{{ $file->fileIcon }} text-xl align-middle"></i>
    </td>
    <td class="pl-2 flex items-center gap-2">
        <div class="text-sm breadcrumbs">
            <ul>
                @if ($includeParentDirectory)
                    <li>
                        <a
                            href="{{ route('browse.index', [$file->directory]) }}"
                            class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis text-base-content/50">
                            @if ($file->directory)
                                {{ $file->directory->name }}
                            @else
                                <i class="fas fa-home"></i>
                            @endif
                        </a>
                    </li> 
                @endif
                <li>
                    @if ($link)
                        <a
                            href="{{ route('files.show', [$file]) }}"
                            class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis"
                        >
                            {{ $file->name }}
                        </a>
                    @else
                        {{ $file->name }}
                    @endif
                </li>
            </ul>
        </div>

        @if ($file->isLatestVersionEncrypted)
            <span class="tooltip" data-tip="{{ __('Encrypted') }}">
                <i class="fa-solid fa-lock text-primary text-xs"></i>
            </span>
        @endif
    </td>
    <td class="max-sm:hidden text-right">
        @if ($file->latestVersion)
            {{ Illuminate\Support\Number::fileSize($file->latestVersion->bytes, maxPrecision: 2) }}
        @else
            -
        @endif
    </td>
    <td>
        @if ($file->latestVersion)
            <i class="fa-solid fa-clock-rotate-left mr-1"></i>
            {{ $file->latestVersion->version }}
        @else
            -
        @endif
    </td>
    <td>
        <x-timestamp :timestamp="$file->fileLastUpdatedAt" :tooltipFallback="__('No versions yet')" />
    </td>
    @if ($deletedAtColumn)
        <td>
            <x-timestamp :timestamp="$file->deleted_at" />
        </td>
    @endif
    <td class="flex justify-end gap-2 items-center">
        {{ $actions ?? '' }}
    </td>
</tr>