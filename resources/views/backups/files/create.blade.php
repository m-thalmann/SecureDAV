<x-app-layout :title="__('Add file to backup')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-file-circle-plus">
            {{ __('Add file to backup') }}
        </x-slot>

        <x-slot name="subtitle">
            <span class="flex items-center gap-2">
                <x-backup-provider-icon :configuration="$configuration" />
                {{ $configuration->label }}
            </span>
        </x-slot>

        <x-file-browser.list :breadcrumbs="$breadcrumbs">
            @foreach ($directories as $directory)
                <x-file-browser.directory-entry :directory="$directory" />
            @endforeach
        
            @foreach ($files as $file)
                <x-file-browser.file-entry :file="$file">
                    <x-slot name="action">
                        <button class="btn btn-sm btn-circle btn-secondary" onclick="onSelectFile(`{{ $file->uuid }}`)">
                            <i class="fa-solid fa-file-circle-plus"></i>
                        </button>
                    </x-slot>
                </x-file-browser.file-entry>
            @endforeach

            @if (count($directories) === 0 && count($files) === 0)
                <li>
                    <a href="#" class="pointer-events-none italic text-base-content/70">
                        {{ __('Empty directory') }}
                    </a>
                </li>
            @endif
        </x-file-browser.list>

        <form action="{{ route('backups.files.store', [$configuration]) }}" method="post">
            @csrf

            <input type="hidden" name="file_uuid" value="" id="selected-file-input" />
        </form>

        <x-slot name="actions" class="mt-4">
            <a href="{{ previousUrl(fallback: route('backups.show', [$configuration])) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
        </x-slot>
    </x-card>

    @push('scripts')
        <script>
            const selectedFileInput = document.getElementById('selected-file-input');

            function onSelectFile(fileUuid) {
                selectedFileInput.value = fileUuid;
                selectedFileInput.form.submit();
            }
        </script>
    @endpush
</x-app-layout>