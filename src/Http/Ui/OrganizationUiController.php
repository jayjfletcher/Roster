<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\CreateOrganizationAction;
use JayI\Roster\Actions\DeleteOrganizationAction;
use JayI\Roster\Actions\LinkOrganizationAction;
use JayI\Roster\Actions\ListAuditEntriesAction;
use JayI\Roster\Actions\ListInvitationsAction;
use JayI\Roster\Actions\ListMembersAction;
use JayI\Roster\Actions\ListOrganizationsAction;
use JayI\Roster\Actions\ListRoleAssignmentsAction;
use JayI\Roster\Actions\ListRolesAction;
use JayI\Roster\Actions\ListScimTokensAction;
use JayI\Roster\Actions\ListSsoConnectionsAction;
use JayI\Roster\Actions\ListTeamsAction;
use JayI\Roster\Actions\RemoveMemberAction;
use JayI\Roster\Actions\ShowOrganizationAction;
use JayI\Roster\Actions\TransferOwnershipAction;
use JayI\Roster\Actions\UnlinkOrganizationAction;
use JayI\Roster\Actions\UpdateOrganizationAction;
use JayI\Roster\Enums\InvitationStatus;
use JayI\Roster\Http\Ui\Concerns\AuthorizesScreens;
use JayI\Roster\Models\Organization;
use JayI\Roster\Support\Users;

/**
 * The Atrium screens for organizations and their members. Each screen
 * validates with its Action's rules and calls that Action.
 */
final class OrganizationUiController
{
    use AuthorizesScreens;

    private const array TABS = ['members', 'teams', 'invitations', 'roles', 'sso', 'scim', 'activity', 'settings'];

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $this->authorizeScreen('roster.organizations.view');

        $filters = $request->validate(ListOrganizationsAction::rules());

        /** @var view-string $view */
        $view = 'roster::ui.organizations.index';

        return view($view, [
            'organizations' => app(ListOrganizationsAction::class)->execute($filters)->withQueryString(),
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
        $model = $this->find($organization);
        $this->authorizeScreen('roster.organizations.view', $model);

        $model = app(ShowOrganizationAction::class)->execute($model);
        $tab = in_array($request->query('tab'), self::TABS, true) ? (string) $request->query('tab') : 'members';

        /** @var view-string $view */
        $view = 'roster::ui.organizations.show';

        // Only the open tab's data: each list is paginated and loaded on its own.
        $page = ['per_page' => 25, 'page' => $request->integer('page', 1)];

        return view($view, [
            'organization' => $model,
            'tab' => $tab,
            'tabs' => self::TABS,
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
                'activity' => ['activity' => app(ListAuditEntriesAction::class)->execute(['organization' => $model] + $page)->withQueryString()],
                'sso' => ['ssoConnections' => app(ListSsoConnectionsAction::class)->execute(['organization' => $model] + $page)->withQueryString()],
                'scim' => [
                    'scimTokens' => app(ListScimTokensAction::class)->execute($model, $page)->withQueryString(),
                    'ssoConnections' => app(ListSsoConnectionsAction::class)->execute(['organization' => $model, 'per_page' => 100]),
                ],
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

    public function destroy(string $organization): RedirectResponse
    {
        $model = $this->find($organization);
        $this->authorizeScreen('roster.organizations.delete', $model);

        app(DeleteOrganizationAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.index')
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

    private function find(string $slug): Organization
    {
        return Organization::query()->where('slug', $slug)->firstOrFail();
    }

    private function backTo(Organization $organization, string $tab, string $message): RedirectResponse
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
