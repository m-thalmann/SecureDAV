<x-app-layout
    :title="__('Files')"
    :header="['icon' => 'fa-solid fa-folder', 'items' => [__('Files') . ' (' . count(Auth::user()->files) . ')']]"
>
    <div class="wrapper" x-data="{ search: '' }">
        <x-content-card class="mb-2" :title="__('Manage')" maxWidth="3xl">
            <x-button :href="route('files.trash')" class="relative mb-4 mr-4">
                <i class="fa-solid fa-trash-can"></i>

                <span
                    class="absolute top-0 left-full bg-red-500 inline-flex items-center justify-center px-2 py-1.5
                           text-xs leading-none rounded-full -translate-x-4 -translate-y-1/3"
                >
                    {{ Auth::user()->files()->onlyTrashed()->count() < 100 ? Auth::user()->files()->onlyTrashed()->count() : '99+' }}
                </span>
            </x-button>

            <x-button :href="route('files.add')" customColor="bg-orange-500 hover:bg-orange-400 active:bg-orange-600 mb-4"><i class="fa-solid fa-plus mr-2"></i> {{ __('Add new file') }}</x-button>
        </x-content-card>

        @if(count(Auth::user()->files) > 0)
            <div class="mb-6 max-w-7xl sm:px-6 lg:px-8 mx-auto">
                <div class="px-4 mt-1 w-full sm:w-1/3 md:w-1/4 sm:px-0 relative">
                    <x-input
                        class="w-full block dark:shadow-lg dark:bg-gray-200 dark:text-black" type="text" placeholder="{{ __('Search...') }}"
                        x-model="search"
                    />
                    <span
                        class="absolute right-7 sm:right-3 top-1/2 -translate-y-1/2 text-black p-1 cursor-pointer hover:text-gray-600"
                        @click="search = ''"
                        x-show="search.length > 0"
                    >
                        <i class="fa-solid fa-times"></i>
                    </span>
                </div>
            </div>

            <div class="grid gap-4 max-w-7xl sm:px-6 lg:px-8 mx-auto lg:grid-cols-3 sm:grid-cols-2 grid-cols-1">
                @foreach (Auth::user()->files as $file)
                    <x-content-card
                        :grid="true"
                        :href="route('files.details', ['file' => $file->uuid])"
                        x-show="search.length === 0 || '{{ strtolower($file->display_name) }}'.includes(search.toLowerCase())"
                    >
                        <div class="content flex">
                            <div class="title flex flex-1 overflow-hidden whitespace-nowrap items-center">
                                <i class="{{ $file->encrypted ? 'fa-solid fa-lock' : 'fa-solid fa-file' }} mr-2 inline-block text-4xl"></i>
                                <div class="inline-block flex-1 overflow-hidden">
                                    <h3 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-300 text-ellipsis w-full overflow-hidden">{{ $file->display_name }}</h3>
                                    @if($file->display_name !== $file->client_name)
                                        <h4 class="text-sm text-gray-400 leading-tight dark:text-gray-500 -mt-1 text-ellipsis w-full overflow-hidden">{{ $file->client_name }}</h4>
                                    @endif
                                </div>
                            </div>
                            <div class="data text-lg flex w-24 items-center justify-end">
                                <a
                                    href="{{ route('files.details', ['file' => $file->uuid]) }}#versions"
                                    class="transition align-middle inline-block mr-1 px-2 py-1 rounded-md bg-gray-200 dark:bg-gray-600 dark:text-white hover:bg-gray-500 hover:text-white"
                                >
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    {{ $file->getAmountVersions() > 0 ? $file->getLastVersion()->version : 0 }}
                                </a>
                                <a
                                    href="{{ route('files.versions.download.latest', ['file' => $file->uuid]) }}"
                                    class="transition align-middle inline-block px-2 py-1 rounded-md
                                           @if($file->getAmountVersions() > 0) bg-gray-200 text-orange-500 hover:bg-gray-400 @else disabled bg-gray-400 text-gray-500 opacity-50 @endif"
                                >
                                    <i class="fa-solid fa-download"></i>
                                </a>
                            </div>
                        </div>
                    </x-content-card>
                @endforeach
            </div>
        @else
            <x-content-card maxWidth="3xl">
                <i class="fa-solid fa-info-circle mr-2"></i>
                {{ __('No files found') }}
            </x-content-card>
        @endif
    </div>
</x-app-layout>