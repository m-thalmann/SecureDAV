<div class="card bg-base-200 shadow-lg max-sm:rounded-none" id="file-versions">
    <div class="card-body">
        <h2 class="card-title mb-4">{{ __('Versions') }} ({{ $file->versions->count() }})</h2>

        <div class="actions flex gap-4 items-center mb-4">
            <a href="{{ route('files.versions.create', ['file' => $file]) }}" class="btn btn-neutral btn-sm">
                <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                {{ __('Create new version') }}
            </a>

            <a href="{{ route('files.versions.latest.edit', ['file' => $file]) }}" class="btn btn-neutral btn-sm" @disabled($file->latestVersion === null)>
                <i class="fa-solid fa-upload mr-2"></i>
                {{ __('Upload file') }}
            </a>
        </div>

        @if ($file->versions->count() > 0)
            <div class="overflow-auto w-full max-h-[25em]">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-center w-0"><i class="fa-solid fa-clock-rotate-left"></i></th>
                            <th>{{ __('Label') }}</th>
                            <th>{{ __('Size') }}</th>
                            <th>{{ __('Created') }}</th>
                            <th>{{ __('Last updated') }}</th>
                            <th class="w-0">{{ __('Checksum (MD5)') }}</th>
                            <th class="w-0"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($file->versions as $version)
                            <tr class="hover:bg-base-100">
                                <td class="text-center">{{ $version->version }}</td>
                                <td>{{ $version->label ?? '-' }}</td>
                                <td>{{ formatBytes($version->bytes) }}</td>
                                <td>
                                    <span class="tooltip" data-tip="{{ $version->created_at }}">{{ $version->created_at->diffForHumans() }}</span>
                                </td>
                                <td>
                                    <span class="tooltip" data-tip="{{ $version->updated_at }}">{{ $version->updated_at->diffForHumans() }}</span>
                                </td>
                                <td class="font-mono">{{ $version->checksum }}</td>
                                <td class="flex gap-2 items-center">
                                    <a href="{{ route('files.versions.show', ['file' => $file->uuid, 'version' => $version->version]) }}" class="btn btn-sm btn-square">
                                        <i class="fas fa-download"></i>
                                    </a>

                                    <x-dropdown
                                        :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                        width="w-56"
                                    >
                                        <li>
                                            <a href="{{ route('files.versions.edit', ['file' => $file->uuid, 'version' => $version->version]) }}">
                                                <i class="fas fa-edit mr-2"></i>
                                                {{ __('Edit label') }}
                                            </a>
                                        </li>

                                        <form
                                            method="POST"
                                            action="{{ route('files.versions.destroy', ['file' => $file->uuid, 'version' => $version->version]) }}"
                                            onsubmit="return confirm('{{ __('Are you sure you want to move this version to trash?') }}')"
                                        >
                                            @method('DELETE')
                                            @csrf
                                            
                                            <li>
                                                <button class="hover:bg-error hover:text-error-content">
                                                    <i class="fas fa-trash mr-2"></i>
                                                    {{ __('Move to trash') }}
                                                </button>
                                            </li>
                                        </form>
                                    </x-dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <span class="italic text-base-content/70">{{ __('This file does not have any versions (yet)') }}</span>
        @endif
    </div>
</div>