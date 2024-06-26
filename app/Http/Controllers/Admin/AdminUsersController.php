<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateUser;
use App\Events\EmailUpdated;
use App\Events\UserDeleted;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminUsersController extends Controller {
    public function index(Request $request): View {
        $search = $request->get('q', default: null);

        $users = User::query()
            ->when($search !== null, function (Builder $query) use ($search) {
                $query
                    ->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            })
            ->withCount('files')
            ->withCount('webDavUsers')
            ->withCount('backupConfigurations')
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

    public function store(
        Request $request,
        CreateUser $createUserAction
    ): RedirectResponse {
        $data = $request->only([
            'name',
            'email',
            'password',
            'password_confirmation',
            'is_admin',
        ]);

        $data['is_admin'] = !!Arr::get($data, 'is_admin', false);

        $user = $createUserAction->handle(
            $data['name'],
            $data['email'],
            $data['password'],
            $data['password_confirmation'],
            $data['is_admin']
        );

        event(new Registered($user));

        return redirect()
            ->route('admin.users.index')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('User created successfully')
                )->forDuration()
            );
    }

    public function edit(User $user): View {
        if ($user->id === auth()->id()) {
            throw new AccessDeniedHttpException(
                __('You cannot edit your own user account.')
            );
        }

        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse {
        if ($user->id === auth()->id()) {
            throw new AccessDeniedHttpException(
                __('You cannot edit your own user account.')
            );
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'is_admin' => ['nullable'],
            'reset_password' => ['nullable'],
        ]);

        $data['is_admin'] = !!Arr::get($data, 'is_admin', false);

        $resetPassword = !!Arr::get($data, 'reset_password', false);
        unset($data['reset_password']);

        $newPassword = null;
        $newEmail = $data['email'] !== $user->email;

        if ($resetPassword) {
            $newPassword = Str::password(16);
            $data['password'] = $newPassword;
        }

        $user->forceFill($data)->save();

        if ($resetPassword) {
            event(new PasswordReset($user));
        }

        if ($newEmail) {
            event(new EmailUpdated($user));

            if (config('app.email_verification_enabled')) {
                $user->sendEmailVerificationNotification();
            }
        }

        $response = redirect()
            ->route('admin.users.index')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('User updated successfully')
                )->forDuration()
            );

        if ($resetPassword) {
            $response->with('generated-password', $newPassword);
        }

        return $response;
    }

    public function destroy(User $user): RedirectResponse {
        if ($user->id === auth()->id()) {
            throw new AccessDeniedHttpException(
                __('You cannot delete your own user account.')
            );
        }

        $user->delete();

        event(new UserDeleted($user));

        return redirect()
            ->route('admin.users.index')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('User deleted successfully')
                )->forDuration()
            );
    }
}
