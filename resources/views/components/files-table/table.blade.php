@props([
    'directoriesCount' => 0,
    'filesCount' => 0,
    'showCountSummary' => true,
    'deletedAtColumn' => false,
])

<table class="table">
    <thead>
        <tr>
            <th class="w-0 pr-0"></th>
            <th class="pl-2">{{ __('Name') }}</th>
            <th class="max-sm:hidden">{{ __('Size') }}</th>
            <th>{{ __('Current version') }}</th>
            <th>{{ __('Last updated') }}</th>
            @if ($deletedAtColumn)
                <th>{{ __('Deleted') }}</th>
            @endif
            <th class="w-0"></th>
        </tr>
    </thead>

    <tbody>
        {{ $slot }}

        @if ($directoriesCount === 0 && $filesCount === 0)
            <tr>
                <td colspan="6" class="text-center italic text-base-content/70">{{ $noItemsContent ?? __('No items') }}</td>
            </tr>
        @elseif ($showCountSummary)
            <tr>
                <td></td>
                <td colspan="5" class="text-sm text-base-content/50 pl-0">
                    {{ trans_choice('{1} 1 directory|[2,*] :count directories', $directoriesCount) }},
                    {{ trans_choice('{1} 1 file|[2,*] :count files', $filesCount) }}
                </td>
            </tr>
        @endif
    </tbody>
</table>