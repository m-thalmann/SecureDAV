@props([
    'directory' => null,
    'hover' => true,
])

<tr {{ $attributes->merge(['class' => $hover ? 'hover' : '']) }}>
    <td class="text-center pr-0">
        <i class="fas fa-folder text-lg text-secondary align-middle"></i>
    </td>
    <td class="pl-2">
        <a
            href="{{ route('browse.index', ['directory' => $directory->uuid]) }}"
            class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis"
        >
            {{ $directory->name }}
        </a>
    </td>
    <td class="max-sm:hidden text-right">-</td>
    <td>-</td>
    <td>
        <span class="tooltip" data-tip="{{ $directory->updated_at }}">{{ $directory->updated_at->diffForHumans() }}</span>
    </td>
    <td class="flex justify-end gap-2 items-center">
        {{ $actions ?? '' }}
    </td>
</tr>