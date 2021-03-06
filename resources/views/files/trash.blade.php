@php
    $trashed = Auth::user()->files()->onlyTrashed()->get();
@endphp

<x-app-layout
    :title="__('Trash')"
    :header="[
        'icon' => 'fa-solid fa-trash-can',
        'items' => [
            [__('Files') => route('files')],
            __('Trash') . ' (' . count($trashed) . ')'
        ]
    ]"
>
    <x-content-card>
        <div>
            <form method="POST" action="{{ route('files.trash.clear') }}" class="inline-block mr-2 mb-4">
                @method('DELETE')
                @csrf

                <x-button :danger="true" onclick="if(!confirm('{{ __('Are you sure you want to permanently delete all trashed files?') }}')) event.preventDefault();">
                    <i class="fa-solid fa-ban mr-2"></i> {{ __('Clear trash') }}
                </x-button>
            </form>
        </div>

        <p class="mt-2 mb-4"><strong>{{ __('Information') }}:</strong> {{ __('Files in the trash are automatically deleted after 30 days.') }}</p>

        <div class="shadow rounded-sm overflow-x-auto sm:m-0 -ml-4 -mr-4">
            <table class="text-center table-auto w-full">
                <thead class="bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-300 whitespace-nowrap">
                    <tr>
                        <th class="px-6 py-2">{{ __('Display name') }}</th>
                        <th class="px-6 py-2">{{ __('Created') }}</th>
                        <th class="px-6 py-2">{{ __('Deleted') }}</th>
                        <th class="px-3 py-2">{{ __('Delete') }}</th>
                        <th class="px-3 py-2">{{ __('Restore') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-700 whitespace-nowrap">
                    @foreach ($trashed as $file)
                        <tr>
                            <td class="px-6 py-3">{{ $file->display_name }}</td>
                            <td class="px-6 py-3">
                                <x-tooltip-element class="cursor-default" :tooltip="$file->created_at->format('d/m/Y H:i:s P')">
                                    {{ $file->created_at->diffForHumans() }}
                                </x-tooltip-element>
                            </td>
                            <td class="px-6 py-3">
                                <x-tooltip-element class="cursor-default" :tooltip="$file->deleted_at->format('d/m/Y H:i:s P')">
                                    {{ $file->deleted_at->diffForHumans() }}
                                </x-tooltip-element>
                            </td>
                            <td class="px-2 py-2 text-xl">
                                <form method="POST" action="{{ route('files.trash.delete', ['file' => $file->uuid]) }}">
                                    @method('DELETE')
                                    @csrf

                                    <button type="submit" onclick="if(!confirm('{{ __('Are you sure you want to permanently delete this file?') }}')) event.preventDefault();">
                                        <i class="fa-solid fa-circle-minus text-red-600"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="px-2 py-2 text-xl">
                                <form method="POST" action="{{ route('files.trash.restore', ['file' => $file->uuid]) }}">
                                    @method('PUT')
                                    @csrf

                                    <button type="submit">
                                        <i class="fa-solid fa-trash-arrow-up text-green-600"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if(count($trashed) === 0)
                        <tr>
                            <td class="px-6 py-3 text-center" colspan="5"><i class="fa-solid fa-info-circle mr-2"></i> {{ __('No files in the trash') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-content-card>
</x-app-layout>