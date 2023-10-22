<x-app-layout :title="__('Add access to file')">
    <x-card dialog>
        <x-slot name="title">
            <i class="fa-solid fa-file-circle-plus mr-2"></i>

            <div>
                <span>{{ __('Add access to file') }}</span>
                <small class="block text-sm font-normal text-base-content/60">
                    <i class="fa-solid fa-user-group mr-1"></i>
                    {{ $accessGroup->label }}
                </small>
            </div>
        </x-slot>

        <x-file-browser.list :breadcrumbs="$breadcrumbs">
            @foreach ($directories as $directory)
                <x-file-browser.directory-entry :directory="$directory" />
            @endforeach
        
            @foreach ($files as $file)
                <x-file-browser.file-entry :file="$file">
                    <x-slot name="action">
                        <button class="btn btn-sm btn-circle btn-secondary" onclick="onSelectFile('{{ $file->uuid }}')">
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

        <form action="{{ route('access-groups.files.store', ['access_group' => $accessGroup->uuid]) }}" method="post">
            @csrf

            <input type="hidden" name="file_uuid" value="" id="selected-file-input" />
        </form>

        <x-slot name="actions" class="mt-4">
            <a href="{{ route('access-groups.show', ['access_group' => $accessGroup->uuid]) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
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