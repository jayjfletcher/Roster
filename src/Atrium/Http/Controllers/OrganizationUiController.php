<?php

declare(strict_types=1);

namespace JayI\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use JayI\Foundation\Audit\Contracts\AuditTrail;
use JayI\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use JayI\Roster\Atrium\RedirectDomains;
use JayI\Roster\Atrium\ScreenAccess;
use JayI\Roster\Domains\Invitation\Actions\ListInvitationsAction;
use JayI\Roster\Domains\Invitation\Enums\InvitationStatus;
use JayI\Roster\Domains\Organization\Actions\AddMemberAction;
use JayI\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\LinkOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\ListMembersAction;
use JayI\Roster\Domains\Organization\Actions\ListOrganizationsAction;
use JayI\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\RemoveMemberAction;
use JayI\Roster\Domains\Organization\Actions\RestoreOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\ShowOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\TransferOwnershipAction;
use JayI\Roster\Domains\Organization\Actions\UnlinkOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Actions\ListRoleAssignmentsAction;
use JayI\Roster\Domains\Role\Actions\ListRolesAction;
use JayI\Roster\Domains\Scim\Actions\ListScimTokensAction;
use JayI\Roster\Domains\Sso\Actions\ListSsoConnectionsAction;
use JayI\Roster\Domains\Team\Actions\ListTeamsAction;
use JayI\Roster\Support\Users;

/**
 * The Atrium screens for organizations and their members. Each screen
 * validates with its Action's rules and calls that Action.
 */
final class OrganizationUiController
{
    use AuthorizesScreens;

    /**
     * Each tab and the permission, in the organization, that opens it. The
     * activity tab shows the organization's history from the audit log, so
     * it is offered only while one (jayi/keen) is installed; the MCP tab
     * holds redirect domains Cortex keeps, so only while jayi/cortex is.
     */
    private const array TABS = [
        'members' => 'roster.members.view',
        'teams' => 'roster.teams.view',
        'invitations' => 'roster.invitations.view',
        'roles' => 'roster.roles.view',
        'sso' => 'roster.sso.view',
        'scim' => 'roster.scim.manage',
        'mcp' => 'roster.organizations.update',
        'activity' => 'roster.audit.view',
        'settings' => 'roster.organizations.view',
    ];

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $within = $this->authorizeList('roster.organizations.view');

        $filters = $request->validate(ListOrganizationsAction::rules());

        /** @var view-string $view */
        $view = 'roster::ui.organizations.index';

        return view($view, [
            'organizations' => app(ListOrganizationsAction::class)->execute($filters, $within)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorizeScreen('roster.organizations.create');

        /** @var view-string $view */
        $view = 'roster::ui.organizations.create';

        return view($view);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('roster.organizations.create');

        $this->splitDomains($request);

        $organization = app(CreateOrganizationAction::class)->execute($request->validate(CreateOrganizationAction::rules()));

        return redirect()
            ->route('atrium.roster.organizations.show', $organization)
            ->with('status', __('roster::roster.organization_created'));
    }

    public function show(Request $request, string $organization): View
    {
        $model = OrganizationModel::withTrashed()->where('slug', $organization)->firstOrFail();
        $this->authorizeScreen('roster.organizations.view', $model);

        if ($model->trashed()) {
            /** @var view-string $deleted */
            $deleted = 'roster::ui.organizations.deleted';

            return view($deleted, ['organization' => $model]);
        }

        $model = app(ShowOrganizationAction::class)->execute($model);

        // Only the tabs the viewer may open here; asking for another is refused.
        $tabs = array_keys(array_filter(self::TABS, fn (string $permission, string $tab): bool => $this->offers($tab)
            && ScreenAccess::allows($permission, $model), ARRAY_FILTER_USE_BOTH));
        $requested = $request->query('tab');

        if (is_string($requested) && array_key_exists($requested, self::TABS)) {
            $this->authorizeScreen(self::TABS[$requested], $model);
        }

        $tab = is_string($requested) && in_array($requested, $tabs, true) ? $requested : ($tabs[0] ?? 'settings');

        /** @var view-string $view */
        $view = 'roster::ui.organizations.show';

        // Only the open tab's data: each list is paginated and loaded on its own.
        $page = ['per_page' => 25, 'page' => $request->integer('page', 1)];

        return view($view, [
            'organization' => $model,
            'tab' => $tab,
            'tabs' => $tabs,
            'pending' => InvitationStatus::Pending,
            'directory' => $this->users,
            ...match ($tab) {
                'members' => ['members' => app(ListMembersAction::class)->execute($model, $page)->withQueryString()],
                'teams' => ['teams' => app(ListTeamsAction::class)->execute($model, $page)->withQueryString()],
                'invitations' => [
                    'invitations' => app(ListInvitationsAction::class)->execute($model, $page)->withQueryString(),
                    // Every team, as the invitation form's options.
                    'teams' => $model->teams()->orderBy('name')->get(['id', 'organization_id', 'name', 'slug']),
                ],
                'roles' => [
                    'roles' => app(ListRolesAction::class)->execute(['organization' => $model, 'per_page' => 100]),
                    'assignments' => app(ListRoleAssignmentsAction::class)->execute(['organization' => $model] + $page)->withQueryString(),
                ],
                'sso' => ['ssoConnections' => app(ListSsoConnectionsAction::class)->execute(['organization' => $model] + $page)->withQueryString()],
                'scim' => [
                    'scimTokens' => app(ListScimTokensAction::class)->execute($model, $page)->withQueryString(),
                    'ssoConnections' => app(ListSsoConnectionsAction::class)->execute(['organization' => $model, 'per_page' => 100]),
                ],
                'mcp' => ['redirectDomains' => app(ListRedirectDomainsAction::class)->execute($model)],
                default => [],
            },
        ]);
    }

    public function update(Request $request, string $organization): RedirectResponse
    {
        $model = $this->find($organization);
        $this->authorizeScreen('roster.organizations.update', $model);

        $request->merge(['auto_join' => $request->boolean('auto_join')]);
        $this->splitDomains($request);

        $model = app(UpdateOrganizationAction::class)->execute($model, $request->validate(UpdateOrganizationAction::rules($model)));

        return $this->backTo($model, 'settings', 'roster::roster.organization_updated');
    }

    public function destroy(Request $request, string $organization): RedirectResponse
    {
        $model = $this->find($organization);
        $this->authorizeScreen('roster.organizations.delete', $model);
        // The page asks for a ticked "I understand" before deleting.
        $request->validate(['confirm' => ['accepted']]);

        app(DeleteOrganizationAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.index', ['trashed' => 'only'])
            ->with('status', __('roster::roster.organization_deleted'));
    }

    public function transfer(Request $request, string $organization): RedirectResponse
    {
        $model = $this->find($organization);
        $this->authorizeScreen('roster.organizations.transfer', $model);

        $model = app(TransferOwnershipAction::class)->execute($model, $request->validate(TransferOwnershipAction::rules()));

        return $this->backTo($model, 'members', 'roster::roster.ownership_transferred');
    }

    public function addMember(Request $request, string $organization): RedirectResponse
    {
        $model = $this->find($organization);
        $this->authorizeScreen('roster.members.manage', $model);

        app(AddMemberAction::class)->execute($model, $request->validate(AddMemberAction::rules()));

        return $this->backTo($model, 'members', 'roster::roster.member_added');
    }

    public function removeMember(string $organization, string $user): RedirectResponse
    {
        $model = $this->find($organization);
        $this->authorizeScreen('roster.members.manage', $model);

        app(RemoveMemberAction::class)->execute($model, $this->users->findOrFail($user));

        return $this->backTo($model, 'members', 'roster::roster.member_removed');
    }

    public function link(Request $request, string $organization): RedirectResponse
    {
        $model = $this->find($organization);
        $this->authorizeScreen('roster.organizations.update', $model);

        app(LinkOrganizationAction::class)->execute($model, $request->validate(LinkOrganizationAction::rules()));

        return $this->backTo($model, 'settings', __('roster::roster.organization_linked'));
    }

    public function unlink(string $organization, string $source): RedirectResponse
    {
        $model = $this->find($organization);
        $this->authorizeScreen('roster.organizations.update', $model);

        app(UnlinkOrganizationAction::class)->execute($model, $source);

        return $this->backTo($model, 'settings', __('roster::roster.organization_unlinked'));
    }

    public function restore(string $organization): RedirectResponse
    {
        $model = OrganizationModel::withTrashed()->where('slug', $organization)->firstOrFail();
        $this->authorizeScreen('roster.organizations.delete', $model);

        app(RestoreOrganizationAction::class)->execute($model);

        return $this->backTo($model, 'members', __('roster::roster.organization_restored'));
    }

    public function purge(Request $request, string $organization): RedirectResponse
    {
        $model = OrganizationModel::withTrashed()->where('slug', $organization)->firstOrFail();
        $this->authorizeScreen('roster.organizations.purge', $model);
        $request->validate(['confirm' => ['accepted']]);

        app(PurgeOrganizationAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.index', ['trashed' => 'only'])
            ->with('status', __('roster::roster.deleted_permanently'));
    }

    /**
     * Whether a tab's package is installed: the audit log for activity,
     * Cortex for MCP redirect domains.
     */
    private function offers(string $tab): bool
    {
        return match ($tab) {
            'activity' => app(AuditTrail::class)->available(),
            'mcp' => RedirectDomains::available(),
            default => true,
        };
    }

    private function find(string $slug): OrganizationModel
    {
        return OrganizationModel::query()->where('slug', $slug)->firstOrFail();
    }

    private function backTo(OrganizationModel $organization, string $tab, string $message): RedirectResponse
    {
        return redirect()
            ->route('atrium.roster.organizations.show', [$organization, 'tab' => $tab])
            ->with('status', __($message));
    }

    /**
     * The form sends domains as one line each.
     */
    private function splitDomains(Request $request): void
    {
        $domains = $request->input('domains');

        if (is_string($domains)) {
            $request->merge(['domains' => array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $domains) ?: [])))]);
        } elseif ($domains === null && $request->has('domains')) {
            $request->merge(['domains' => []]);
        }
    }
}
