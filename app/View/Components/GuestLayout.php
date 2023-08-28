<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component {
    public function __construct(public readonly ?string $title = null) {
    }

    public function render(): View {
        return view('layouts.guest');
    }
}
