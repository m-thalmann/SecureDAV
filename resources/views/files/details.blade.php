<x-app-layout
    :title="$file->display_name"
    :header="[
        'icon' => 'fa-solid fa-file',
        'items' => [
            [__('Files') => route('files')],
            $file->display_name
        ]
    ]"
>
    <x-content-card :title="__('General')" class="mb-4">
        <table class="mb-4 w-full">
            <tr>
                <td class="pr-4 font-bold w-36">{{ __('Name') }}:</td>
                <td>{{ $file->display_name }}</td>
            </tr>
            @if($file->display_name !== $file->client_name)
                <tr>
                    <td class="pr-4 font-bold">{{ __('Original name') }}:</td>
                    <td>{{ $file->client_name }}</td>
                </tr>
            @endif
            <tr>
                <td class="pr-4 font-bold">{{ __('MIME-Type') }}:</td>
                <td>{{ $file->mime_type }}</td>
            </tr>
            <tr>
                <td class="pr-4 font-bold">{{ __('Created') }}:</td>
                <td>{{ $file->created_at }}</td>
            </tr>
            <tr>
                <td class="pr-4 font-bold">{{ __('Encrypted') }}:</td>
                <td>
                    <input
                        type="checkbox"
                        class="rounded border-gray-300 text-orange-500 shadow-sm dark:border-none cursor-not-allowed"
                        @checked($file->encrypted) disabled>
                </td>
            </tr>
            <tr>
                <td class="pr-4 font-bold pt-4">{{ __('WebDAV URL') }}:</td>
                <td class="flex w-full sm:w-3/4 md:w-1/2 pt-4">
                    @php
                        $webDavRoute = route('webdav', ["path" => $file->uuid]);
                    @endphp
            
                    <x-input
                        class="mr-4 flex-auto"
                        type="text"
                        id="webDavRouteInput"
                        value="{{ $webDavRoute }}"
                        size="1"
                        readonly />
        
                    <x-button
                        type="button"
                        onclick="document.getElementById('webDavRouteInput').select();
                                 document.execCommand('copy');
                                 changeClass(this.getElementsByTagName('i')[0], 'fa-solid fa-check w-4 text-green-500', 1500)"
                    >
                        <i class="fa-solid fa-copy w-4"></i>
                    </x-button>
                </td>
            </tr>
        </table>

        <div>
            <x-button :href="route('files.versions.download.latest', ['file' => $file->uuid])" class="mr-2 mb-2">
                <i class="fa-solid fa-download mr-2"></i> {{ __('Download latest version') }}
            </x-button>

            <form method="POST" action="{{ route('files.delete', ['file' => $file->uuid]) }}" class="inline-block mr-2 mb-2">
                @method('DELETE')
                @csrf
    
                <x-button :danger="true" onclick="if(!confirm('{{ __('Are you sure you want to move this file to the trash?') }}')) event.preventDefault();">
                    <i class="fa-solid fa-trash-can mr-2"></i> {{ __('Delete file') }}
                </x-button>
            </form>
        </div>
    </x-content-card>

    @php
        $accessUsers = $file->accessUsers();
    @endphp
    <x-content-card :title="__('Access users') . ' (' . count($accessUsers) . ')'" class="mb-4">
        <div class="mb-4">
            <x-button href="#" class="mb-4"> <!-- TODO: add route -->
                <i class="fa-solid fa-user-plus mr-2"></i> {{ __('Create new access user') }}
            </x-button>
        </div>

        <div class="shadow rounded-sm overflow-x-auto sm:m-0 -ml-4 -mr-4">
            <table class="text-center table-auto w-full">
                <thead class="bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-300 whitespace-nowrap">
                    <tr>
                        <th class="px-6 py-2">{{ __('Username') }}</th>
                        <th class="px-6 py-2">{{ __('Write') }}</th>
                        <th class="px-6 py-2">{{ __('Access all files') }}</th>
                        <th class="px-6 py-2">{{ __('Last access') }}</th>
                        <th class="px-6 py-2">{{ __('Active tokens') }}</th>
                        <th class="px-6 py-2">{{ __('Generate token') }}</th>
                        <th class="px-6 py-2">{{ __('View') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-700 whitespace-nowrap">
                    @foreach ($accessUsers as $accessUser)
                        <tr>
                            <td class="px-6 py-3">{{ $accessUser->username }}</td>
                            <td class="px-6 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-orange-500 shadow-sm dark:border-none cursor-not-allowed"
                                    @checked(!$accessUser->readonly) disabled>
                            </td>
                            <td class="px-6 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-orange-500 shadow-sm dark:border-none cursor-not-allowed"
                                    @checked($accessUser->access_all) disabled>
                            </td>
                            <td class="px-6 py-3">
                                @if($accessUser->lastAccess() !== null)
                                    <x-tooltip-element class="cursor-default" :tooltip="$accessUser->lastAccess()->format('d/m/Y H:i:s P')">
                                        {{ $accessUser->lastAccess()->diffForHumans() }}
                                    </x-tooltip-element>
                                @else
                                    {{ __('Never') }}
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                {{ $accessUser->activeTokens()->count() }}
                                /
                                {{ $accessUser->tokens()->count() }}
                            </td>
                            <td class="px-2 py-2 text-xl">
                                <form method="POST" action="{{ route("access.tokens.generate", ["accessUser" => $accessUser->id]) }}">
                                    @csrf
                        
                                    <button>
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="px-2 py-2 text-xl">
                                <a href="#"> <!-- TODO: add route -->
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    @if(count($accessUsers) === 0)
                        <tr>
                            <td class="px-6 py-3 text-center" colspan="7"><i class="fa-solid fa-info-circle mr-2"></i> {{ __('No access-users for this file') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-content-card>

    <x-content-card id="versions" :title="__('Versions') . ' (' . $file->getAmountVersions() . ')'">
        <div class="mb-4">
            <x-button :href="route('files.versions.trash', ['file' => $file->uuid])" class="relative mb-4 mr-4">
                <i class="fa-solid fa-trash-can"></i>

                <span
                    class="absolute top-0 left-full bg-red-500 inline-flex items-center justify-center px-2 py-1.5
                           text-xs leading-none rounded-full -translate-x-4 -translate-y-1/3"
                >
                    {{ $file->versions()->onlyTrashed()->count() < 100 ? $file->versions()->onlyTrashed()->count() : '99+' }}
                </span>
            </x-button>

            <form method="POST" action="{{ route('files.versions.store', ['file' => $file->uuid]) }}" class="inline-block mr-2 mb-4">
                @csrf
    
                <x-button>
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i> {{ __('Create new version') }}
                </x-button>
            </form>

            <x-button :href="route('files.versions.upload.view', ['file' => $file->uuid])" class="mb-4">
                <i class="fa-solid fa-upload mr-2"></i> {{ __('Upload file') }}
            </x-button>
        </div>

        <div class="shadow rounded-sm overflow-x-auto sm:m-0 -ml-4 -mr-4">
            <table class="text-center table-auto w-full">
                <thead class="bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-300 whitespace-nowrap">
                    <tr>
                        <th class="px-6 py-2"><i class="fa-solid fa-clock-rotate-left"></i></th>
                        <th class="px-6 py-2">{{ __('Created') }}</th>
                        <th class="px-6 py-2">{{ __('Last updated') }}</th>
                        <th class="px-6 py-2">{{ __('Size') }}</th>
                        @if ($file->encrypted)
                            <th class="px-6 py-2">{{ __('Size on disk') }}</th>
                        @endif
                        <th class="px-3 py-2">{{ __('Delete') }}</th>
                        {{-- <th class="px-3 py-2">{{ __('Share') }}</th> --}}
                        <!-- TODO: share -->
                        <th class="px-3 py-2">{{ __('Download') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-700 whitespace-nowrap">
                    @foreach ($file->versions as $version)
                        <tr>
                            <td class="px-6 py-3">{{ $version->version }}</td>
                            <td class="px-6 py-3">
                                <x-tooltip-element class="cursor-default" :tooltip="$version->created_at->format('d/m/Y H:i:s P')">
                                    {{ $version->created_at->diffForHumans() }}
                                </x-tooltip-element>
                            </td>
                            <td class="px-6 py-3">
                                <x-tooltip-element class="cursor-default" :tooltip="$version->updated_at->format('d/m/Y H:i:s P')">
                                    {{ $version->updated_at->diffForHumans() }}
                                </x-tooltip-element>
                            </td>
                            <td class="px-6 py-3">
                                {{ formatBytes($version->bytes) }}
                            </td>
                            @if ($file->encrypted)
                                <td class="px-6 py-3">
                                    {{ formatBytes($version->bytesOnDisk()) }}
                                </td>
                            @endif
                            <td class="px-2 py-2 text-xl">
                                <form method="POST" action="{{ route('files.versions.delete', ['file' => $file->uuid, 'version' => $version->version]) }}">
                                    @method('DELETE')
                                    @csrf
                        
                                    <button type="submit" onclick="if(!confirm('{{ __('Are you sure you want to move this version to the trash?') }}')) event.preventDefault();">
                                        <i class="fa-solid fa-trash-can text-red-600"></i>
                                    </button>
                                </form>
                            </td>
                            {{-- <td class="px-2 py-2 text-xl ">
                                <a href="{{ route('files.versions.download', ['file' => $file->uuid, 'version' => $version->version]) }}">
                                    <i class="fa-solid fa-share"></i>
                                </a>
                            </td> --}}
                            <td class="px-2 py-2 text-xl">
                                <a href="{{ route('files.versions.download', ['file' => $file->uuid, 'version' => $version->version]) }}">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                            </td>
                        </tr>                
                    @endforeach
                    @if(count($file->versions) === 0)
                        <tr>
                            <!-- TODO: on share updated colspan -->
                            <td class="px-6 py-3 text-center" colspan="{{ $file->encrypted ? '7' : '6' }}"><i class="fa-solid fa-info-circle mr-2"></i> {{ __('No versions for this file') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-content-card>
</x-app-layout>