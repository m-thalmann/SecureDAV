<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component {
    public function __construct(public readonly ?string $title = null) {
    }

    public function render(): View {
        return view('layouts.app', [
            'user' => authUser(),
            'notifications' => [
                'latestUnread' => authUser()
                    ->unreadNotifications()
                    ->limit(5)
                    ->get(),
                'totalUnread' => authUser()
                    ->unreadNotifications()
                    ->count(),
                'total' => authUser()
                    ->notifications()
                    ->count(),
            ],
        ]);
    }
}
