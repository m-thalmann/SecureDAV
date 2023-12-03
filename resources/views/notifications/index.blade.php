<x-app-layout :title="__('Notifications')">
    <div class="md:w-2/3 md:mx-auto flex flex-col gap-6">
        <x-header-title iconClass="fa-solid fa-bell">
            <x:slot name="title">
                {{ __('Notifications') }}

                <small>({{ $notifications->total() }})</small>
            </x:slot>
        </x-header-title>

        <div class="flex flex-col gap-4">
            @foreach ($notifications as $notification)
                <x-card id="notification-{{ $notification->id }}" class="target:ring-2">
                    <x-slot name="title" class="text-base">
                        @if ($notification->unread())
                            <i class="fa-solid fa-circle text-xs text-primary mr-1"></i>
                        @endif

                        {{ $notification->data['title'] }}

                        <span>&sdot;</span>

                        <span class="tooltip" data-tip="{{ $notification->created_at }}">
                            <small class="text-xs text-base-content/75 align-middle font-normal">{{ $notification->created_at->diffForHumans() }}</small>
                        </span>
                    </x-slot>

                    <p class="text-base-content/75">
                        {{ $notification->data['body'] }}
                    </p>

                    <x-slot name="actions">
                        <form action="{{ route('notifications.update', [$notification]) }}" method="POST">
                            @method('PUT')
                            @csrf

                            <input type="hidden" name="read" value="{{ !$notification->read() ? 'true' : 'false' }}" />

                            <button type="submit" class="btn btn-square {{ $notification->read() ? 'btn-neutral' : 'btn-secondary' }}">
                                @if ($notification->read())
                                    <i class="fa-solid fa-envelope"></i>
                                @else
                                    <i class="fa-solid fa-envelope-open"></i>
                                @endif
                            </button>
                        </form>

                        <form action="{{ route('notifications.destroy', [$notification]) }}" method="POST">
                            @method('DELETE')
                            @csrf

                            <button type="submit" class="btn btn-square btn-error">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </x-slot>
                </x-card>
            @endforeach

            @if (count($notifications) === 0)
                <div class="alert text-left text-base-content/75 italic my-4">
                    <i class="fa-solid fa-envelope-open"></i>
                    {{ __('No notifications') }}
                </div>
            @endif
        </div>

        {{ $notifications->links() }}
    </div>
</x-app-layout>