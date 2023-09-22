<x-app-layout :title="$file->name">
    <h2 class="px-4">
        <div class="breadcrumbs">
            <ul>
                <li class="h-6">
                    <a href="{{ route('browse.index') }}" class="!no-underline"><i class="fas fa-home"></i></a>
                </li>

                @foreach ($file->directory?->breadcrumbs ?? [] as $breadcrumb)
                    <li>
                        <a href="{{ route('browse.index', ['directory' => $breadcrumb->uuid]) }}" class="!inline-block max-w-[16ch] overflow-hidden text-ellipsis">{{ $breadcrumb->name }}</a>
                    </li>
                @endforeach

                <li class="flex items-center gap-2"><i class="fas fa-file"></i> {{ $file->name }}</li>
            </ul>
        </div>
    </h2>

    <div class="card bg-base-200 shadow-lg max-sm:rounded-none">
        <div class="card-body">
            <h2 class="card-title mb-4">{{ __('General') }}</h2>

            <div class="w-full overflow-auto">
                <table class="mb-4 w-full">
                    <tr>
                        <td class="pr-4 font-bold w-px whitespace-nowrap">{{ __('MIME-Type') }}:</td>
                        <td>{{ $file->mime_type }}</td>
                    </tr>
                    <tr>
                        <td class="pr-4 font-bold w-px whitespace-nowrap">{{ __('Created') }}:</td>
                        <td>{{ $file->created_at }}</td>
                    </tr>
                    <tr>
                        <td class="pr-4 font-bold w-px whitespace-nowrap">{{ __('Encrypted') }}:</td>
                        <td>
                            <input type="checkbox" class="checkbox checkbox-sm cursor-not-allowed align-middle" @checked($file->encrypted) disabled />
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
                <a href="#" class="btn btn-neutral">
                    <i class="fas fa-download"></i>
                    {{ __('Download latest version') }}
                </a>

                <form method="POST" action="{{ route('files.destroy', ['file' => $file->uuid]) }}" onsubmit="return confirm('{{ __('Are you sure you want to move this file to trash?') }}')">
                    @method('DELETE')
                    @csrf
                    
                    <button class="btn btn-error">
                        <i class="fas fa-trash"></i>
                        {{ __('Move file to trash') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>