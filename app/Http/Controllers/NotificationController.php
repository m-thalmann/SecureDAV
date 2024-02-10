<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller {
    public const ITEMS_PER_PAGE = 4;

    public function __construct() {
        $this->authorizeResource(DatabaseNotification::class, 'notification');
    }

    public function index(Request $request): View {
        return view('notifications.index', [
            'notifications' => authUser()
                ->notifications() // already sorted by latest
                ->orderBy('id', 'asc')
                ->paginate(perPage: static::ITEMS_PER_PAGE),
        ]);
    }

    public function show(DatabaseNotification $notification): RedirectResponse {
        $amountBefore = authUser()
            ->notifications()
            ->getQuery()
            ->where(function (Builder $query) use ($notification) {
                $query
                    ->where('created_at', '>', $notification->created_at)
                    ->orWhere(function (Builder $query) use ($notification) {
                        $query
                            ->where('created_at', $notification->created_at)
                            ->where('id', '<', $notification->id);
                    });
            })
            ->count();

        $page = (int) ceil(($amountBefore + 1) / static::ITEMS_PER_PAGE);

        return redirect()
            ->route('notifications.index', ['page' => $page])
            ->withFragment("notification-{$notification->id}");
    }

    public function update(
        Request $request,
        DatabaseNotification $notification
    ): RedirectResponse {
        $read = $request->boolean('read');

        if ($read) {
            $notification->markAsRead();
        } else {
            $notification->markAsUnread();
        }

        return back();
    }

    public function markAllAsRead(): RedirectResponse {
        authUser()->unreadNotifications->markAsRead();

        return back();
    }

    public function destroy(
        DatabaseNotification $notification
    ): RedirectResponse {
        $notification->delete();

        return back();
    }

    public function destroyAll(): RedirectResponse {
        authUser()
            ->notifications()
            ->delete();

        return back();
    }
}
