<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
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

    public function create(): View {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Rules\Password::defaults(),
            ],
            'is_admin' => ['nullable'],
        ]);

        $data['is_admin'] = !!Arr::get($data, 'is_admin', false);

        $user = new User();
        $user->forceFill($data);
        $user->encryption_key = Str::random(16);
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('User created successfully')
                )->forDuration()
            );
    }
}
