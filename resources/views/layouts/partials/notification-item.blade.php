<div class="dropdown dropdown-end">
    <label tabindex="0" class="btn btn-ghost btn-circle">
        <div class="indicator">
            <i class="fa-solid fa-bell"></i>
            @if ($notifications['totalUnread'] > 0)
                <span class="badge badge-xs badge-primary indicator-item"></span>
            @endif
        </div>
    </label>
    <ul tabindex="0" class="menu dropdown-content mt-3 z-[1] p-2 shadow bg-base-300 rounded-box w-64">
        @foreach ($notifications['latestUnread'] as $notification)
            <li>
                <a href="#">
                    <div>
                        <p class="mb-1">
                            {{ $notification->data['title'] }}
                            <span>&sdot;</span>
                            <small class="text-xs text-base-content/75">{{ $notification->created_at->diffForHumans() }}</small>
                        </p>
                        <span class="text-xs text-base-content/75 line-clamp-2">{{ $notification->data['body'] }}</span>
                    </div>
                </a>
            </li>
        @endforeach

        @if ($notifications['totalUnread'] > count($notifications['latestUnread']))
            <span class="px-4 py-1 text-xs">
                {{ trans_choice('{1} +1 more notification|[2,*] +:count more unread notifications', $notifications['total'] - count($notifications['latestUnread'])) }}</span>
        @endif

        @if ($notifications['totalUnread'] === 0)
            <li class="pointer-events-none mt-2">
                <span class="italic text-base-content/75">
                    <i class="fa-solid fa-envelope-open"></i>
                    {{ __('No unread notifications') }}
                </span>
            </li>
        @endif

        <span class="divider my-2"></span>

        <li>
            <a href="#">
                {{ __('View all notifications') }}
                <span class="text-xs">({{ $notifications['total'] }})</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </li>
    </ul>
</div>