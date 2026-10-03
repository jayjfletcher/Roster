<?php

declare(strict_types=1);

namespace JayI\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use JayI\Roster\Domains\Permission\Actions\ListPermissionsAction;
use JayI\Roster\Domains\Role\Actions\AssignRoleAction;
use JayI\Roster\Domains\Role\Actions\CreateRoleAction;
use JayI\Roster\Domains\Role\Actions\DeleteRoleAction;
use JayI\Roster\Domains\Role\Actions\ListRolesAction;
use JayI\Roster\Domains\Role\Actions\RevokeRoleAction;
use JayI\Roster\Domains\Role\Actions\ShowRoleAction;
use JayI\Roster\Domains\Role\Actions\UpdateRoleAction;
use JayI\Roster\Domains\Role\Enums\RoleScope;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Role\Models\RoleModel;
use JayI\Roster\Support\Scopes;
use JayI\Roster\Support\Users;

/**
 * The Atrium screens for roles, and for assigning them to users.
 */
final class RoleUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $filters = $request->validate(ListRolesAction::rules());
        $within = $this->authorizeList('roster.roles.view', Scopes::fromInput($filters['organization'] ?? null));

        /** @var view-string $view */
        $view = 'roster::ui.roles.index';

        return view($view, [
            'roles' => app(ListRolesAction::class)->execute($filters, $within)->withQueryString(),
            'filters' => $filters,
            'scopes' => RoleScope::cases(),
            'permissions' => app(ListPermissionsAction::class)->execute(['per_page' => 200])->items(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('roster.roles.manage', Scopes::fromInput($request->input('organization')));

        $role = app(CreateRoleAction::class)->execute($request->validate(CreateRoleAction::rules()), $this->actor($request));

        return redirect()
            ->route('atrium.roster.roles.show', $role->id)
            ->with('status', __('roster::roster.role_created'));
    }

    public function show(string $role): View
    {
        $model = $this->find($role);
        $this->authorizeScreen('roster.roles.view', $model->organization);

        /** @var view-string $view */
        $view = 'roster::ui.roles.show';

        return view($view, [
            'role' => app(ShowRoleAction::class)->execute($model),
            'permissions' => app(ListPermissionsAction::class)->execute(['per_page' => 200])->items(),
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        $model = $this->find($role);
        $this->authorizeScreen('roster.roles.manage', $model->organization);

        $request->merge(['permissions' => (array) $request->input('permissions', [])]);

        app(UpdateRoleAction::class)->execute($model, $request->validate(UpdateRoleAction::rules()), $this->actor($request));

        return redirect()
            ->route('atrium.roster.roles.show', $model->id)
            ->with('status', __('roster::roster.role_updated'));
    }

    public function destroy(string $role): RedirectResponse
    {
        $model = $this->find($role);
        $this->authorizeScreen('roster.roles.manage', $model->organization);

        app(DeleteRoleAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.roles.index')
            ->with('status', __('roster::roster.role_deleted'));
    }

    public function assign(Request $request, string $user): RedirectResponse
    {
        $target = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.roles.assign', Scopes::fromInput($request->input('organization'), $request->input('team')));

        app(AssignRoleAction::class)->execute($target, $request->validate(AssignRoleAction::rules()), $this->actor($request));

        return redirect()
            ->route('atrium.roster.users.show', $target->getRouteKey())
            ->with('status', __('roster::roster.role_assigned'));
    }

    public function revoke(Request $request, string $user, string $assignment): RedirectResponse
    {
        $target = $this->users->findOrFail($user);
        $model = RoleAssignmentModel::query()->where('user_id', $target->getKey())->whereKey($assignment)->firstOrFail();
        $this->authorizeScreen('roster.roles.assign', $model->team ?? $model->organization);

        app(RevokeRoleAction::class)->execute($model, $this->actor($request));

        return redirect()
            ->route('atrium.roster.users.show', $target->getRouteKey())
            ->with('status', __('roster::roster.role_revoked'));
    }

    private function find(string $id): RoleModel
    {
        return RoleModel::query()->whereKey($id)->firstOrFail();
    }

    private function actor(Request $request): ?Model
    {
        $user = $request->user();

        return $user instanceof Model ? $user : null;
    }
}
