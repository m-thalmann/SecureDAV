<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileSettingsController extends Controller {
    public function edit(Request $request): View {
        return view('settings.profile.edit', [
            'user' => $request->user(),
        ]);
    }
}

