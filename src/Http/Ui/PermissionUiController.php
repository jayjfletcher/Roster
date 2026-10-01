<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Roster\Actions\CreatePermissionAction;
use JayI\Roster\Actions\DeletePermissionAction;
use JayI\Roster\Actions\ListPermissionsAction;
use JayI\Roster\Actions\UpdatePermissionAction;
use JayI\Roster\Http\Ui\Concerns\AuthorizesScreens;
use JayI\Roster\Models\Permission;

final class PermissionUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('roster.roles.view');

        /** @var view-string $view */
        $view = 'roster::ui.permissions.index';

        return view($view, [
            'permissions' => app(ListPermissionsAction::class)->execute($request->validate(ListPermissionsAction::rules()))->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('roster.roles.manage');

        app(CreatePermissionAction::class)->execute($request->validate(CreatePermissionAction::rules()));

        return $this->back('roster::roster.permission_created');
    }

    public function update(Request $request, string $permission): RedirectResponse
    {
        $this->authorizeScreen('roster.roles.manage');

        $request->merge(['description' => $request->input('description')]);

        app(UpdatePermissionAction::class)->execute($this->find($permission), $request->validate(UpdatePermissionAction::rules()));

        return $this->back('roster::roster.permission_updated');
    }

    public function destroy(string $permission): RedirectResponse
    {
        $this->authorizeScreen('roster.roles.manage');

        app(DeletePermissionAction::class)->execute($this->find($permission));

        return $this->back('roster::roster.permission_deleted');
    }

    private function find(string $name): Permission
    {
        return Permission::query()->where('name', $name)->firstOrFail();
    }

    private function back(string $message): RedirectResponse
    {
        return redirect()->route('atrium.roster.permissions.index')->with('status', __($message));
    }
}
