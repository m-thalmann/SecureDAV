<x-app-layout :title="$file->fileName">
    <div class="files-breadcrumbs flex items-center px-4">
        <x-breadcrumbs :file="$file" />

        <span class="flex-1"></span>

        <x-dropdown align="end">
            <li>
                <a href="{{ route('files.edit', ['file' => $file->uuid]) }}">
                    <i class="fas fa-edit mr-2"></i>
                    {{ __('Edit file') }}
                </a>
            </li>

            <form
                method="POST"
                action="{{ route('files.destroy', ['file' => $file->uuid]) }}"
                onsubmit="return confirm(`{{ __('Are you sure you want to move this file to trash?') }}`)"
            >
                @method('DELETE')
                @csrf
                
                <li>
                    <button class="hover:bg-error hover:text-error-content">
                        <i class="fas fa-trash mr-2"></i>
                        {{ __('Move file to trash') }}
                    </button>
                </li>
            </form>
        </x-dropdown>
    </div>

    <x-header-title :iconClass="$file->fileIcon">
        <x:slot name="title">
            {{ $file->fileName }}

            @if ($file->isEncrypted)
                <span class="tooltip" data-tip="{{ __('Encrypted') }}">
                    <i class="fa-solid fa-lock text-primary text-sm"></i>
                </span>
            @else
                <span class="tooltip" data-tip="{{ __('Not encrypted') }}">
                    <i class="fa-solid fa-lock-open text-warning text-sm"></i>
                </span>
            @endif
        </x:slot>

        <x-slot name="subtitle">
            @if ($file->mime_type)
                <span class="tooltip" data-tip="{{ __('MIME-Type') }}">{{ $file->mime_type }}</span>
                
                <span class="mx-1">&CenterDot;</span>
            @endif

            <span class="tooltip" data-tip="{{ __('Created') }}">{{ $file->created_at }}</span>
        </x-slot>
    </x-header-title>

    <div class="flex gap-4 justify-between items-center px-4 sm:px-0">
        <x-form-field name="web-dav-url" class="w-full md:w-2/3 lg:w-1/2">
            <x-slot name="label">{{ __('WebDAV URL') }}</x-slot>

            <div class="flex gap-2 items-center">
                <x-input name="web-dav-url" class="input-sm bg-base-200" value="<TODO: webdav url>" readonly />
        
                <button
                    class="btn btn-sm btn-neutral"
                    onclick="document.getElementById('web-dav-url').select();
                                document.execCommand('copy');
                                changeClass(this.getElementsByTagName('i')[0], 'fa-solid fa-check', 1000)"
                >
                    <i class="fa-solid fa-copy"></i>
                </button>
            </div>
        </x-form-field>

        <a href="{{ route('files.versions.latest.show', ['file' => $file->uuid]) }}" class="btn btn-primary btn-circle" @disabled($file->latestVersion === null)>
            <i class="fas fa-download"></i>
        </a>
    </div>

    @if ($file->description)
        <x-card>
            <x-slot name="title" class="mb-4">
                {{ __('Description') }}
            </x-slot>

            <p>{{ $file->description }}</p>
        </x-card>
    @endif

    @include('files.partials.file-versions')

    @include('files.partials.file-access-groups')
</x-app-layout>