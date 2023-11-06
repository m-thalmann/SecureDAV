@props([
    'directory' => null,
    'hover' => true,
    'includeParentDirectory' => false
])

<tr {{ $attributes->merge(['class' => $hover ? 'hover' : '']) }}>
    <td class="text-center pr-0">
        <i class="fas fa-folder text-lg text-secondary align-middle"></i>
    </td>
    <td class="pl-2">
        <div class="text-sm breadcrumbs">
            <ul>
                @if ($includeParentDirectory)
                    <li>
                        <a
                            href="{{ route('browse.index', [$directory->parentDirectory]) }}"
                            class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis text-base-content/50"
                        >
                            @if ($directory->parentDirectory)
                                {{ $directory->parentDirectory->name }}
                            @else
                                <i class="fas fa-home"></i>
                            @endif
                        </a>
                    </li> 
                @endif
                <li>
                    <a
                        href="{{ route('browse.index', [$directory]) }}"
                        class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis"
                    >
                        {{ $directory->name }}
                    </a>
                </li>
            </ul>
        </div>
    </td>
    <td colspan="3"></td>
    <td class="flex justify-end gap-2 items-center">
        {{ $actions ?? '' }}
    </td>
</tr>