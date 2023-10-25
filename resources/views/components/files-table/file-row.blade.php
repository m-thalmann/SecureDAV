@props([
    'file' => null,
    'hover' => true,
])

<tr {{ $attributes->merge(['class' => $hover ? 'hover' : '']) }}>
    <td class="text-center pr-0">
        <i class="{{ $file->fileIcon }} text-xl align-middle"></i>
    </td>
    <td class="pl-2">
        <a
            href="{{ route('files.show', ['file' => $file->uuid]) }}"
            class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis"
        >{{ $file->name }}</a>

        @if ($file->isEncrypted)
            <span class="tooltip ml-2" data-tip="{{ __('Encrypted') }}">
                <i class="fa-solid fa-lock text-primary text-xs"></i>
            </span>
        @endif
    </td>
    <td class="max-sm:hidden text-right">
        @if ($file->latestVersion)
            {{ formatBytes($file->latestVersion->bytes) }}
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
        <span class="tooltip" data-tip="{{ $file->latestVersion?->updated_at ?? __('No versions yet') }}">{{ $file->latestVersion?->updated_at?->diffForHumans() ?? '-' }}</span>
    </td>
    <td class="flex justify-end gap-2 items-center">
        {{ $actions ?? '' }}
    </td>
</tr>