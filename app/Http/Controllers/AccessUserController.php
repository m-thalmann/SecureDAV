<?php

namespace App\Http\Controllers;

use App\Models\AccessUser;
use Illuminate\View\View;

class AccessUserController extends Controller {
    public function __construct() {
        $this->authorizeResource(AccessUser::class);
    }

    public function index(): View {
        return view('access-users.index', [
            'accessUsers' => AccessUser::query()
                ->withCount('files')
                ->forUser(auth()->user())
                ->get(),
        ]);
    }
}

