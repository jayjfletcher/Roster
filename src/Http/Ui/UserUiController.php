<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use JayI\Roster\Actions\ApproveUserAction;
use JayI\Roster\Actions\CreateUserAction;
use JayI\Roster\Actions\DeactivateUserAction;
use JayI\Roster\Actions\DeleteUserAction;
use JayI\Roster\Actions\JoinOrganizationsByDomainAction;
use JayI\Roster\Actions\ListAuditEntriesAction;
use JayI\Roster\Actions\ListRoleAssignmentsAction;
use JayI\Roster\Actions\ListSsoIdentitiesAction;
use JayI\Roster\Actions\ListUserPermissionsAction;
use JayI\Roster\Actions\ListUsersAction;
use JayI\Roster\Actions\PurgeUserAction;
use JayI\Roster\Actions\ReactivateUserAction;
use JayI\Roster\Actions\RejectUserAction;
use JayI\Roster\Actions\RestoreUserAction;
use JayI\Roster\Actions\ShowUserAction;
use JayI\Roster\Actions\SuspendUserAction;
use JayI\Roster\Actions\SwitchContextAction;
use JayI\Roster\Actions\UpdateProfileAction;
use JayI\Roster\Actions\UpdateUserAction;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Http\Ui\Concerns\AuthorizesScreens;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Role;
use JayI\Roster\Roster;
use JayI\Roster\Support\Users;

/**
 * The Atrium screens for users. Every screen validates with its Action's own
 * rules and calls that Action, so the dashboard, HTTP API and MCP behave the
 * same.
 */
final class UserUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $this->authorizeScreen('roster.users.view');

        $filters = $request->validate(ListUsersAction::rules());

        /** @var view-string $view */
        $view = 'roster::ui.users.index';

        return view($view, [
            'users' => app(ListUsersAction::class)->execute($filters)->withQueryString(),
            'filters' => $filters,
            'softDeletes' => $this->users->softDeletes(),
            'statuses' => UserStatus::cases(),
            'directory' => $this->users,
        ]);
    }

    public function create(): View
    {
        $this->authorizeScreen('roster.users.create');

        /** @var view-string $view */
        $view = 'roster::ui.users.create';

        return view($view, ['hasName' => $this->users->column('name') !== null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('roster.users.create');

        $this->dropBlankPassword($request);

        $user = app(CreateUserAction::class)->execute($request->validate(CreateUserAction::rules()));

        return redirect()
            ->route('atrium.roster.users.show', $user->getRouteKey())
            ->with('status', __('roster::roster.user_created'));
    }

    public function show(string $user): View
    {
        $target = $this->users->findWithTrashedOrFail($user);
        $this->authorizeScreen('roster.users.view', null, $target);

        if ($this->users->trashed($target)) {
            /** @var view-string $deleted */
            $deleted = 'roster::ui.users.deleted';

            return view($deleted, ['user' => $target, 'directory' => $this->users]);
        }

        $model = app(ShowUserAction::class)->execute($target);

        /** @var view-string $view */
        $view = 'roster::ui.users.show';

        return view($view, [
            'user' => $model,
            'profile' => $model->getRelation('rosterProfile'),
            'status' => $this->users->status($model),
            'directory' => $this->users,
            'memberships' => Membership::query()->where('user_id', $model->getKey())->whereHas('organization')->with(['organization', 'teams'])->get(),
            'currentOrganization' => app(Roster::class)->organization($model),
            'currentTeam' => app(Roster::class)->team($model),
            'assignments' => app(ListRoleAssignmentsAction::class)->execute(['user' => $model, 'per_page' => 100]),
            'effective' => app(ListUserPermissionsAction::class)->execute($model),
            'assignableRoles' => Role::query()->with('organization')->orderBy('scope')->orderBy('name')->get(),
            'activity' => app(ListAuditEntriesAction::class)->execute(['user' => $model, 'per_page' => 10]),
            'ssoIdentities' => app(ListSsoIdentitiesAction::class)->execute(['user' => $model, 'per_page' => 50]),
        ]);
    }

    public function update(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.update');

        $this->dropBlankPassword($request);

        app(UpdateUserAction::class)->execute($model, $request->validate(UpdateUserAction::rules($model)));

        return $this->backTo($model, 'roster::roster.user_updated');
    }

    public function profile(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.update', null, $model);

        app(UpdateProfileAction::class)->execute($model, $request->validate(UpdateProfileAction::rules()));

        return $this->backTo($model, 'roster::roster.profile_updated');
    }

    /**
     * The user page's single status form: the new status picks the Action.
     */
    public function status(Request $request, string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.manage-status');

        $target = $request->validate(['status' => ['required', Rule::in([UserStatus::Active->value, UserStatus::Suspended->value, UserStatus::Deactivated->value])]])['status'];

        return match (UserStatus::from($target)) {
            UserStatus::Suspended => $this->suspend($request, $user),
            UserStatus::Deactivated => $this->deactivate($request, $user),
            default => $this->reactivate($request, $user),
        };
    }

    public function approve(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.approve');

        app(ApproveUserAction::class)->execute($model, $request->validate(ApproveUserAction::rules()), $this->actor($request));

        // Back to wherever it was clicked: the user's page, the Users list
        // or an organization's members.
        return redirect()->back(fallback: route('atrium.roster.users.show', $model->getRouteKey()))
            ->with('status', __('roster::roster.user_approved'));
    }

    public function reject(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.approve');

        app(RejectUserAction::class)->execute($model, $request->validate(RejectUserAction::rules()), $this->actor($request));

        return $this->backTo($model, 'roster::roster.user_rejected');
    }

    public function suspend(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.manage-status');

        app(SuspendUserAction::class)->execute($model, $request->validate(SuspendUserAction::rules()), $this->actor($request));

        return $this->backTo($model, 'roster::roster.user_suspended');
    }

    public function deactivate(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.manage-status');

        app(DeactivateUserAction::class)->execute($model, $request->validate(DeactivateUserAction::rules()), $this->actor($request));

        return $this->backTo($model, 'roster::roster.user_deactivated');
    }

    public function reactivate(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.manage-status');

        app(ReactivateUserAction::class)->execute($model, $request->validate(ReactivateUserAction::rules()), $this->actor($request));

        return $this->backTo($model, 'roster::roster.user_reactivated');
    }

    public function switchContext(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.update', null, $model);

        app(SwitchContextAction::class)->execute($model, $request->validate(SwitchContextAction::rules()));

        return $this->backTo($model, 'roster::roster.context_switched');
    }

    public function domainJoin(string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.update');

        $joined = app(JoinOrganizationsByDomainAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.users.show', $model->getRouteKey())
            ->with('status', __('roster::roster.domain_joined', ['count' => $joined->count()]));
    }

    public function restore(string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.delete');

        $model = app(RestoreUserAction::class)->execute($this->users->findWithTrashedOrFail($user));

        return $this->backTo($model, 'roster::roster.user_restored');
    }

    public function purge(Request $request, string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.purge');
        $request->validate(['confirm' => ['accepted']]);

        app(PurgeUserAction::class)->execute($this->users->findWithTrashedOrFail($user), $this->actor($request));

        return redirect()
            ->route('atrium.roster.users.index', ['trashed' => 'only'])
            ->with('status', __('roster::roster.deleted_permanently'));
    }

    public function destroy(Request $request, string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.delete');
        // The page asks for a ticked "I understand" before deleting.
        $request->validate(['confirm' => ['accepted']]);

        app(DeleteUserAction::class)->execute($this->users->findOrFail($user), $this->actor($request));

        return redirect()
            ->route('atrium.roster.users.index', $this->users->softDeletes() ? ['trashed' => 'only'] : [])
            ->with('status', __('roster::roster.user_deleted'));
    }

    private function backTo(Model $user, string $message): RedirectResponse
    {
        return redirect()
            ->route('atrium.roster.users.show', $user->getRouteKey())
            ->with('status', __($message));
    }

    private function actor(Request $request): ?Model
    {
        $user = $request->user();

        return $user instanceof Model ? $user : null;
    }

    /**
     * A blank password field means "keep the current one" on edit and "set a
     * random one" on create, so it never reaches validation.
     */
    private function dropBlankPassword(Request $request): void
    {
        if (blank($request->input('password'))) {
            $request->request->remove('password');
        }
    }
}
