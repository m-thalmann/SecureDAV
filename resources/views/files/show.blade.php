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

            <form method="POST" action="{{ route('files.destroy', ['file' => $file->uuid]) }}" onsubmit="return confirm('{{ __('Are you sure you want to move this file to trash?') }}')">
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

    @if ($file->description)
        <div class="card bg-base-200 shadow-lg max-sm:rounded-none">
            <div class="card-body">
                <h2 class="card-title mb-4">{{ __('Description') }}</h2>

                <p>
                    {{ $file->description }}
                </p>
            </div>
        </div>
    @endif

    <div class="card bg-base-200 shadow-lg max-sm:rounded-none">
        <div class="card-body">
            <h2 class="card-title mb-4">{{ __('General') }}</h2>

            <div class="w-full overflow-auto">
                <table class="mb-4 w-full">
                    <tr>
                        <td class="pr-4 font-bold w-px whitespace-nowrap">{{ __('MIME-Type') }}:</td>
                        <td>{{ $file->mime_type ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="pr-4 font-bold w-px whitespace-nowrap">{{ __('Created') }}:</td>
                        <td>{{ $file->created_at }}</td>
                    </tr>
                    <tr>
                        <td class="pr-4 font-bold w-px whitespace-nowrap">{{ __('Encrypted') }}:</td>
                        <td>
                            <input type="checkbox" class="checkbox checkbox-sm cursor-not-allowed align-middle" @checked($file->isEncrypted) disabled />
                        </td>
                    </tr>
                    <tr>
                        <td class="pr-4 pt-4 font-bold w-px whitespace-nowrap">{{ __('WebDAV URL') }}:</td>
                        <td class="flex gap-4 w-full sm:w-3/4 lg:w-2/3 pt-4">
                            <input type="text" id="web-dav-url" class="input input-sm w-full" value="<TODO: webdav url>" readonly />
                            <button
                                class="btn btn-sm btn-neutral"
                                onclick="document.getElementById('web-dav-url').select();
                                         document.execCommand('copy');
                                         changeClass(this.getElementsByTagName('i')[0], 'fa-solid fa-check', 1000)"
                            >
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card-actions">
                <a href="{{ route('files.versions.latest.show', ['file' => $file->uuid]) }}" class="btn btn-neutral btn-sm" @disabled($file->latestVersion === null)>
                    <i class="fas fa-download"></i>
                    {{ __('Download latest version') }}
                </a>
            </div>
        </div>
    </div>

    @include('files.partials.file-versions')
</x-app-layout>