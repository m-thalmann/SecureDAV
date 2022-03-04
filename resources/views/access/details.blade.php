<x-app-layout
    :title="__('Access')"
    :header="[
        'icon' => 'fa-solid fa-shield-alt',
        'items' => [
            [__('Access') => route('access')],
            $accessUser->username
        ]
    ]"
>
    <x-content-card :title="__('General')" class="mb-8">
        <table class="mb-4 w-full">
            <tr>
                <td class="pr-4 font-bold pb-2">{{ __('Username') }}:</td>
                <td class="flex w-full sm:w-1/2 md:w-1/3 pb-2">
                    <x-input
                        class="mr-4 flex-auto"
                        type="text"
                        id="usernameInput"
                        value="{{ $accessUser->username }}"
                        size="1"
                        readonly />
        
                    <x-button
                        type="button"
                        onclick="document.getElementById('usernameInput').select();
                                 document.execCommand('copy');
                                 changeClass(this.getElementsByTagName('i')[0], 'fa-solid fa-check w-4 text-green-500', 1500)"
                    >
                        <i class="fa-solid fa-copy w-4"></i>
                    </x-button>
                </td>
            </tr>
            <tr>
                <td class="pr-4 font-bold w-36">{{ __('Label') }}:</td>
                <td>{{ $accessUser->label ? $accessUser->label : '-' }}</td>
            </tr>
            <tr>
                <td class="pr-4 font-bold">{{ __('Read only') }}:</td>
                <td>
                    <form method="POST" action="{{ route("access.readonly.update", ["accessUser" => $accessUser->id]) }}">
                        @method('PUT')
                        @csrf

                        @if(!$accessUser->readonly)
                            <input type="hidden" name="readonly" value="on">
                        @endif
            
                        <button onclick="if(!confirm('{{ __('Are you sure you want to toggle read only for this access-user?') }}')) event.preventDefault();">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-orange-500 shadow-sm dark:border-none pointer-events-none"
                                @checked($accessUser->readonly) disabled>
                        </button>
                    </form>
                </td>
            </tr>
            <tr>
                <td class="pr-4 font-bold">{{ __('Access all files') }}:</td>
                <td>
                    <form method="POST" action="{{ route("access.access_all.update", ["accessUser" => $accessUser->id]) }}">
                        @method('PUT')
                        @csrf

                        @if(!$accessUser->access_all)
                            <input type="hidden" name="access_all" value="on">
                        @endif
            
                        <button onclick="if(!confirm('{{ __('Are you sure you want to toggle access to all files for this access-user?') }}')) event.preventDefault();">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-orange-500 shadow-sm dark:border-none pointer-events-none"
                                @checked($accessUser->access_all) disabled>
                        </button>
                    </form>
                </td>
            </tr>
        </table>
    </x-content-card>

    @php
        $tokens = $accessUser->tokens()->orderBy("last_access", "desc")->get();
    @endphp
    <x-content-card :title="__('Tokens') . ' (' . count($tokens) . ')'" class="mb-8">
        <div class="mb-4">
            <form method="POST" action="{{ route("access.tokens.generate", ["accessUser" => $accessUser->id]) }}" class="mb-4">
                @csrf
    
                <x-button>
                    <i class="fa-solid fa-key mr-2"></i> {{ __('Generate new token') }}
                </x-button>
            </form>
            <!-- TODO: button to create a token with input value -->
        </div>

        <div class="shadow rounded-sm overflow-x-auto sm:m-0 -ml-4 -mr-4">
            <table class="text-center table-auto w-full">
                <thead class="bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-300 whitespace-nowrap">
                    <tr>
                        <th class="px-6 py-2">{{ __('Label') }}</th>
                        <th class="px-6 py-2">{{ __('Created') }}</th>
                        <th class="px-6 py-2">{{ __('Last access') }}</th>
                        <th class="px-2 py-2">{{ __('Active') }}</th>
                        <th class="px-2 py-2">{{ __('Delete') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-700 whitespace-nowrap">
                    @foreach ($tokens as $token)
                        <tr>
                            <td class="px-6 py-3">
                                {{ $token->label ? $token->label : '-' }}
                                <!-- TODO: make label editable -->
                            </td>
                            <td class="px-6 py-3">
                                <x-tooltip-element class="cursor-default" :tooltip="$token->created_at->format('d/m/Y H:i:s P')">
                                    {{ $token->created_at->diffForHumans() }}
                                </x-tooltip-element>
                            </td>
                            <td class="px-6 py-3">
                                @if($token->last_access !== null)
                                    <x-tooltip-element class="cursor-default" :tooltip="$token->last_access->format('d/m/Y H:i:s P')">
                                        {{ $token->last_access->diffForHumans() }}
                                    </x-tooltip-element>
                                @else
                                    {{ __('Never') }}
                                @endif
                            </td>
                            <td class="px-2 py-3">
                                <form method="POST" action="{{ route("access.tokens.active.update", ["accessUserToken" => $token->id]) }}">
                                    @method('PUT')
                                    @csrf

                                    @if(!$token->active)
                                        <input type="hidden" name="active" value="on">
                                    @endif
                        
                                    <button>
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 text-orange-500 shadow-sm dark:border-none pointer-events-none"
                                            @checked($token->active) disabled>
                                    </button>
                                </form>
                            </td>
                            <td class="px-2 py-2 text-xl">
                                <form method="POST" action="{{ route('access.tokens.destroy', ["accessUserToken" => $token->id]) }}">
                                    @method('DELETE')
                                    @csrf
                        
                                    <button type="submit" onclick="if(!confirm('{{ __('Are you sure you want to permanently delete this token?') }}')) event.preventDefault();">
                                        <i class="fa-solid fa-trash-can text-red-600"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if(count($tokens) === 0)
                        <tr>
                            <td class="px-6 py-3 text-center" colspan="5"><i class="fa-solid fa-info-circle mr-2"></i> {{ __('No tokens for this access-user') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-content-card>

    @php
        $accessFiles = $accessUser->getFiles()->get();
    @endphp
    <x-content-card :title="__('Access files') . ' (' . count($accessFiles) . ')'">
        <!-- TODO: add functionality to add access file(s) -->
        <div class="shadow rounded-sm overflow-x-auto sm:m-0 -ml-4 -mr-4">
            <table class="text-center table-auto w-full">
                <thead class="bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-300 whitespace-nowrap">
                    <tr>
                        <th class="px-6 py-2">{{ __('Display name') }}</th>
                        <th class="px-6 py-2">{{ __('Original name') }}</th>
                        <th class="px-6 py-2">{{ __('Created') }}</th>
                        <th class="px-2 py-2">{{ __('Encrypted') }}</th>
                        <th class="px-2 py-2">{{ __('Revoke access') }}</th>
                        <th class="px-2 py-2">{{ __('View') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-700 whitespace-nowrap">
                    @foreach ($accessFiles as $file)
                        <tr>
                            <td class="px-6 py-3">
                                {{ $file->display_name }}
                            </td>
                            <td class="px-6 py-3">
                                {{ $file->client_name }}
                            </td>
                            <td class="px-6 py-3">
                                <x-tooltip-element class="cursor-default" :tooltip="$file->created_at->format('d/m/Y H:i:s P')">
                                    {{ $file->created_at->diffForHumans() }}
                                </x-tooltip-element>
                            </td>
                            <td class="px-2 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-orange-500 shadow-sm dark:border-none cursor-not-allowed"
                                    @checked($file->encrypted) disabled>
                            </td>
                            <td class="px-2 py-2 text-xl">
                                @if(!$accessUser->access_all || $file->user_id !== Auth::user()->id)
                                    <form method="POST" action="{{ route("access.files.revoke", ["accessUser" => $accessUser->id, "file" => $file->uuid]) }}">
                                        @method('DELETE')
                                        @csrf
                            
                                        <button>
                                            <i class="fa-solid fa-user-xmark text-red-600"></i>
                                        </button>
                                    </form>
                                @else
                                    <button disabled class="cursor-not-allowed">
                                        <i class="fa-solid fa-user-xmark text-gray-300 dark:text-gray-500"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-xl">
                                <a href="{{ route('files.details', ["file" => $file->uuid]) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    @if(count($accessFiles) === 0)
                        <tr>
                            <td class="px-6 py-3 text-center" colspan="6"><i class="fa-solid fa-info-circle mr-2"></i> {{ __('No files for this access-user') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-content-card>
</x-app-layout>