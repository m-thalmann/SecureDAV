<x-app-layout
    :title="__('Access')"
    :header="['icon' => 'fa-solid fa-shield-alt', 'items' => [__('Access')]]"
>
    @php
        $accessUsers = Auth::user()->accessUsers;
    @endphp
    <x-content-card :title="__('Access users') . ' (' . count($accessUsers) . ')'" class="mb-4">
        <div class="mb-4">
            <x-button :href="route('access.add')" class="mb-4">
                <i class="fa-solid fa-user-plus mr-2"></i> {{ __('Create new access user') }}
            </x-button>
        </div>

        <div class="shadow rounded-sm overflow-x-auto sm:m-0 -ml-4 -mr-4">
            <table class="text-center table-auto w-full">
                <thead class="bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-300 whitespace-nowrap">
                    <tr>
                        <th class="px-6 py-2">{{ __('Username') }}</th>
                        <th class="px-6 py-2">{{ __('Label') }}</th>
                        <th class="px-2 py-2">{{ __('Read only') }}</th>
                        <th class="px-2 py-2">{{ __('Access all files') }}</th>
                        <th class="px-6 py-2">{{ __('Last access') }}</th>
                        <th class="px-6 py-2">{{ __('Active tokens') }}</th>
                        <th class="px-6 py-2">{{ __('Accessible files') }}</th>
                        <th class="px-2 py-2">{{ __('View') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-700 whitespace-nowrap">
                    @foreach ($accessUsers as $accessUser)
                        <tr>
                            <td class="px-6 py-3">{{ $accessUser->username }}</td>
                            <td class="px-6 py-3">{{ $accessUser->label ? $accessUser->label : '-' }}</td>
                            <td class="px-2 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-orange-500 shadow-sm dark:border-none cursor-not-allowed"
                                    @checked($accessUser->readonly) disabled>
                            </td>
                            <td class="px-2 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-orange-500 shadow-sm dark:border-none cursor-not-allowed"
                                    @checked($accessUser->access_all) disabled>
                            </td>
                            <td class="px-6 py-3">
                                @if($accessUser->getLastAccess() !== null)
                                    <x-tooltip-element class="cursor-default" :tooltip="$accessUser->getLastAccess()->format('d/m/Y H:i:s P')">
                                        {{ $accessUser->getLastAccess()->diffForHumans() }}
                                    </x-tooltip-element>
                                @else
                                    {{ __('Never') }}
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                {{ $accessUser->getActiveTokens()->count() }}
                                /
                                {{ $accessUser->tokens()->count() }}
                            </td>
                            <td class="px-6 py-3">
                                {{ $accessUser->getFiles()->count() }}
                            </td>
                            <td class="px-2 py-2 text-xl">
                                <a href="{{ route('access.details', ["accessUser" => $accessUser->id]) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    @if(count($accessUsers) === 0)
                        <tr>
                            <td class="px-6 py-3 text-center" colspan="8"><i class="fa-solid fa-info-circle mr-2"></i> {{ __('No access-users for this file') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-content-card>
</x-app-layout>