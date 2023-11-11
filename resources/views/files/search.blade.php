<x-app-layout :title="__('Search files')">
    <form action="{{ route('files.search') }}" method="GET" class="w-full sm:w-2/3 mx-auto mb-12 relative">
        @csrf

        <label for="search-input" class="absolute top-1/2 left-4 -translate-y-1/2">
            <i class="fa-solid fa-search"></i>
        </label>

        <input value="{{ $search }}" placeholder="{{ __('Search files...') }}" autofocus name="q" class="input bg-base-200 w-full pl-10 max-sm:rounded-none shadow-md" id="search-input" />
    </form>

    @if ($files !== null)
        <x-header-title >
            <x:slot name="title">
                {{ __('Results') }} <small>({{ $files->total() }})</small>
            </x:slot>
        </x-header-title>

        <div class="overflow-auto w-full">
            <x-files-table.table :filesCount="count($files)" :showCountSummary="false">
                @foreach ($files as $file)
                    <x-files-table.file-row :includeParentDirectory="true" :file="$file">
                        <x-slot name="actions">
                            @if ($file->latestVersion !== null)
                                <a href="{{ route('files.versions.latest.show', [$file]) }}" class="btn btn-sm btn-square">
                                    <i class="fas fa-download"></i>
                                </a>
                            @endif
                        </x-slot>
                    </x-files-table.file-row>
                @endforeach

                <x-slot name="noItemsContent">
                    {{ __('No matching files found') }}
                </x-slot>
            </x-files-table.table>
        </div>

        {{ $files->links() }}
    @endif
</x-app-layout>