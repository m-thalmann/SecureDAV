<x-card id="file-versions">
    <x-slot name="title" :amount="$file->versions->count()" class="mb-4">
        {{ __('Versions') }}
    </x-slot>

    <x-slot name="titleSuffix">
        <form action="{{ route('files.auto-version-hours.update', [$file]) }}" method="post" class="font-normal flex items-center gap-4">
            @method('PUT')
            @csrf

            <span class="text-sm flex items-center gap-1">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                {{ __('Auto version') }}

                <x-card-dropdown>
                    {{ __('A new version of the file will be created automatically after the given delay (when updating the file)') }}
                </x-card-dropdown>
            </span>

            @php
                $selectedAutoVersionHoursFound = false;
            @endphp

            <select name="hours" class="select select-sm" onchange="this.form.submit()">
                <option value="" @selected($file->auto_version_hours === null)>{{ __('Disabled') }}</option>

                @foreach (\App\Models\File::AUTO_VERSION_HOURS as $hours)
                    <option value="{{ $hours }}" @selected($file->auto_version_hours === $hours)>{{ formatHours($hours) }}</option>

                    @if ($file->auto_version_hours === $hours)
                        @php
                            $selectedAutoVersionHoursFound = true;
                        @endphp
                    @endif
                @endforeach

                @if (!$selectedAutoVersionHoursFound && $file->auto_version_hours !== null)
                    <option value="{{ $file->auto_version_hours }}" selected>{{ formatHours($file->auto_version_hours) }}</option>
                @endif
            </select>
        </form>
    </x-slot>

    <div class="actions flex gap-4 items-center mb-4">
        <a href="{{ route('files.versions.create', [$file]) }}" class="btn btn-neutral btn-sm">
            <i class="fa-solid fa-clock-rotate-left mr-2"></i>
            {{ __('Create new version') }}
        </a>

        <a href="{{ route('files.versions.latest.edit', [$file]) }}" class="btn btn-neutral btn-sm" @disabled($file->latestVersion === null)>
            <i class="fa-solid fa-upload mr-2"></i>
            {{ __('Upload file') }}
        </a>
    </div>

    @if ($file->versions->count() > 0)
        <div class="overflow-auto w-full bg-base-100 rounded-md max-h-[25em]">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-center w-0"><i class="fa-solid fa-clock-rotate-left"></i></th>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Size') }}</th>
                        <th>{{ __('MIME-Type') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th>{{ __('Last updated') }}</th>
                        <th class="w-0">{{ __('Checksum (MD5)') }}</th>
                        <th class="w-0"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($file->versions as $version)
                        <tr>
                            <td class="text-center">{{ $version->version }}</td>
                            <td>{{ $version->label ?? '-' }}</td>
                            <td>{{ Illuminate\Support\Number::fileSize($version->bytes, maxPrecision: 2) }}</td>
                            <td>{{ $version->mime_type ?? '-' }}</td>
                            <td>
                                <x-timestamp :timestamp="$version->created_at" />
                            </td>
                            <td>
                                <x-timestamp :timestamp="$version->file_updated_at" />
                            </td>
                            <td class="font-mono">{{ $version->checksum }}</td>
                            <td class="flex gap-2 items-center">
                                <a href="{{ route('files.versions.show', [$file, $version]) }}" class="btn btn-sm btn-square">
                                    <i class="fas fa-download"></i>
                                </a>

                                <x-dropdown
                                    :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                    width="w-52"
                                >
                                    <li>
                                        <a href="{{ route('files.versions.edit', [$file, $version]) }}">
                                            <i class="fas fa-edit w-6"></i>
                                            {{ __('Edit label') }}
                                        </a>
                                    </li>

                                    <form
                                        method="POST"
                                        action="{{ route('files.versions.destroy', [$file, $version]) }}"
                                        onsubmit="return confirm(`{{ __('Are you sure you want to permanently delete this version?') }}`)"
                                    >
                                        @method('DELETE')
                                        @csrf
                                        
                                        <li>
                                            <button class="hover:bg-error hover:text-error-content">
                                                <i class="fas fa-trash w-6"></i>
                                                {{ __('Permanently delete') }}
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
</x-card>