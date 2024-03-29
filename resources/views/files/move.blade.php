<x-app-layout :title="__('Move file')">
    <x-card dialog>
        <x-slot name="title" icon="fa-solid fa-share-from-square">
            {{ __('Move file') }}
        </x-slot>
            
        <x-slot name="subtitle" icon="{{ $file->fileIcon }}">
            <div class="breadcrumbs py-0">
                <ul>
                    <li>
                        <a
                            href="{{ route('browse.index', [$file->directory]) }}"
                            class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis text-base-content/30">
                            @if ($file->directory)
                                {{ $file->directory->name }}
                            @else
                                <i class="fas fa-home"></i>
                            @endif
                        </a>
                    </li> 
                    <li>
                        <a
                            href="{{ route('files.show', [$file]) }}"
                            class="link link-hover max-w-[48ch] overflow-hidden text-ellipsis"
                        >
                            {{ $file->name }}
                        </a>
                    </li>
                </ul>
            </div>
        </x-slot>

        <div class="h-2"></div>

        <x-session-message :message="session('session-message')" class="mb-3"></x-session-message>

        <x-file-browser.list :breadcrumbs="$breadcrumbs">
            @foreach ($directories as $directoryRow)
                <x-file-browser.directory-entry :directory="$directoryRow" />
            @endforeach

            @foreach ($files as $fileRow)
                <x-file-browser.file-entry :file="$fileRow">
                    @if ($fileRow->id === $file->id)
                        <x-slot name="suffix">
                            <span class="tooltip tooltip-left ml-2" data-tip="{{ __('This file') }}">
                                <i class="fa-solid fa-thumbtack text-primary"></i>
                            </span>
                        </x-slot>
                    @endif
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

        <x-slot name="actions" class="mt-4">
            <a href="{{ previousUrl(fallback: route('files.show', [$file])) }}" class="btn btn-neutral">{{ __('Cancel') }}</a>

            <form action="{{ route('files.move', [$file]) }}" method="post">
                @method('PUT')
                @csrf
    
                <input type="hidden" name="directory_uuid" value="{{ $currentDirectory?->uuid }}" />

                <button class="btn btn-primary">{{ __('Move here') }}</button>
            </form>
        </x-slot>
    </x-card>
</x-app-layout>