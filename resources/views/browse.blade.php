<x-app-layout :title="__('Browse files')">
    <div class="files-breadcrumbs flex items-center px-4">
        <div class="breadcrumbs">
            <ul>
                <li class="h-6">
                    <a href="{{ route('browse.index') }}" class="!no-underline"><i class="fas fa-home"></i></a>
                </li>

                @foreach ($breadcrumbs as $breadcrumb)
                    <li>
                        <a href="{{ route('browse.index', ['directory' => $breadcrumb->uuid]) }}" class="!inline-block max-w-[16ch] overflow-hidden text-ellipsis">{{ $breadcrumb->name }}</a>
                    </li>
                @endforeach

                <li></li>
            </ul>
        </div>

        <div @class([
            'dropdown',
            'dropdown-end' => count($breadcrumbs) > 2,
        ])>
            <label tabindex="0" class="btn btn-sm btn-circle">
                <i class="fas fa-add"></i>
            </label>
            <ul tabindex="0" class="dropdown-content z-[1] menu p-2 mt-1 shadow bg-base-200 rounded-box w-44">
                <li>
                    <a href="#">
                        <i class="fas fa-folder w-6"></i>
                        {{ __('New directory') }}
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-file w-6"></i>
                        {{ __('New file') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="overflow-auto w-full">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th class="max-sm:hidden">{{ __('Size') }}</th>
                    <th>{{ __('Current version') }}</th>
                    <th>{{ __('Last updated') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($directories as $directory)
                    <tr class="hover">
                        <td>
                            <a href="{{ route('browse.index', ['directory' => $directory->uuid]) }}" class="flex items-center group">
                                <i class="fas fa-folder text-secondary w-6"></i>
                                <span class="group-hover:underline max-w-[48ch] overflow-hidden text-ellipsis">
                                    {{ $directory->name }}
                                </span>
                            </a>
                        </td>
                        <td class="max-sm:hidden">-</td>
                        <td>-</td>
                        <td>TODO</td>
                    </tr>
                @endforeach

                @foreach ($files as $file)
                    <tr class="hover">
                        <!-- TODO: use better file icons for mime types -->
                        <td>
                            <a href="#" class="flex items-center group">
                                <i class="fas fa-file w-6"></i>
                                <span class="group-hover:underline">
                                    {{ $file->display_name }}
                                </span>
                            </a>
                        </td>
                        <td class="max-sm:hidden">TODO</td>
                        <td>TODO</td>
                        <td>{{ $file->updated_at->diffForHumans() }}</td>
                    </tr>
                @endforeach

                @if (count($directories) === 0 && count($files) === 0)
                    <tr>
                        <td colspan="4" class="text-center italic text-base-content/70">{{ __('This directory is empty') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</x-app-layout>