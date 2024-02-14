<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUsersController extends Controller {
    public function index(Request $request): View {
        $search = $request->get('q', default: null);

        $users = User::query()
            ->when($search !== null, function (Builder $query) use ($search) {
                $query
                    ->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            })
            ->paginate(perPage: 10)
            ->appends(['q' => $search]);

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }
}
