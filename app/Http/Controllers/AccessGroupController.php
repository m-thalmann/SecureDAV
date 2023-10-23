<?php

namespace App\Http\Controllers;

use App\Models\AccessGroup;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class AccessGroupController extends Controller {
    public function __construct() {
        $this->authorizeResource(AccessGroup::class);
    }

    public function index(): View {
        return view('access-groups.index', [
            'accessGroups' => AccessGroup::query()
                ->withCount('files')
                ->withCount('users')
                ->forUser(authUser())
                ->get(),
        ]);
    }

    public function create(): View {
        return view('access-groups.create');
    }

    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'label' => ['string', 'max:128'],
            'readonly' => ['nullable'],
        ]);

        $accessGroup = authUser()
            ->accessGroups()
            ->forceCreate([
                'label' => $data['label'],
                'readonly' => !!Arr::get($data, 'readonly', false),
                'active' => true,
            ]);

        return redirect()
            ->route('access-groups.show', $accessGroup->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Access group created successfully')
                )->forDuration()
            );
    }

    public function show(AccessGroup $accessGroup): View {
        return view('access-groups.show', [
            'accessGroup' => $accessGroup
                ->load('users')
                ->load('files.latestVersion'),
        ]);
    }

    public function edit(AccessGroup $accessGroup): View {
        return view('access-groups.edit', [
            'accessGroup' => $accessGroup,
        ]);
    }

    public function update(
        Request $request,
        AccessGroup $accessGroup
    ): RedirectResponse {
        $data = $request->validate([
            'label' => ['string', 'max:128'],
            'readonly' => ['nullable'],
            'active' => ['nullable'],
        ]);

        $accessGroup->update([
            'label' => $data['label'],
            'readonly' => !!Arr::get($data, 'readonly', false),
            'active' => !!Arr::get($data, 'active', false),
        ]);

        return redirect()
            ->route('access-groups.show', $accessGroup->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Access group updated successfully')
                )->forDuration()
            );
    }

    public function destroy(AccessGroup $accessGroup): RedirectResponse {
        $accessGroup->delete();

        return redirect()
            ->route('access-groups.index')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Access group successfully deleted.')
                )->forDuration()
            );
    }
}

