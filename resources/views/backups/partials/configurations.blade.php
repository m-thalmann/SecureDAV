<x-card>
    <x-slot name="title" icon="fa-solid fa-rotate" :amount="$configurations->total()" class="mb-4">
        {{ __('Configured Backups') }}
    </x-slot>

    <div class="overflow-auto w-full bg-base-100 rounded-md">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Label') }}</th>
                    <th>{{ __('Provider') }}</th>
                    <th>{{ __('Files') }}</th>
                    <th>{{ __('Last run') }}</th>
                    <th class="w-0"></th>
                </tr>
            </thead>

            <tbody>
                @foreach ($configurations as $configuration)
                    <tr>
                        <td>
                            <a href="{{ route('backups.show', [$configuration]) }}" class="link underline-offset-2">{{ $configuration->label }}</a>
                        </td>
                        <td>
                            <span class="flex gap-2 items-center">
                                @isset ($configuration->provider_class::getDisplayInformation()['iconUrl'])
                                    <img src="{{ $configuration->provider_class::getDisplayInformation()['iconUrl'] }}" class="w-4 h-4 max-w-none rounded-sm" />
                                @else
                                    <i class="{{ $configuration->provider_class::getDisplayInformation()['icon'] }}"></i>
                                @endif

                                <span class="underline underline-offset-4 decoration-dashed" title="{{ $configuration->provider_class::getDisplayInformation()['description'] }}">
                                    {{ $configuration->provider_class::getDisplayInformation()['name'] }}
                                </span>
                            </span>
                        </td>
                        <td>
                            {{ $configuration->files_count }}
                        </td>
                        <td>
                            <x-timestamp :timestamp="$configuration->last_run_at" :fallback="__('Never')" />
                        </td>
                        <td>
                            <x-dropdown
                                :position-aligned="getTableLoopDropdownPositionAligned($loop->index, $loop->count, 2)"
                                width="w-52"
                            >
                                <li>
                                    <a href="#">
                                        <i class="fas fa-edit w-6"></i>
                                        {{ __('Edit') }}
                                    </a>
                                </li>

                                <form
                                    method="POST"
                                    action="{{ route('backups.destroy', [$configuration]) }}"
                                    onsubmit="return confirm(`{{ __('Are you sure you want to delete this backup configuration?') }}`)"
                                >
                                    @method('DELETE')
                                    @csrf
                                    
                                    <li>
                                        <button class="hover:bg-error hover:text-error-content">
                                            <i class="fas fa-trash w-6"></i>
                                            {{ __('Delete') }}
                                        </button>
                                    </li>
                                </form>
                            </x-dropdown>
                        </td>
                    </tr>
                @endforeach

                @if (count($configurations) === 0)
                    <tr>
                        <td colspan="4" class="text-center italic text-base-content/70">{{ __('No backups configured yet') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</x-card>

{{ $configurations->links() }}