<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Organization\Actions\ListMembersAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\DeleteTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\ShowTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\UpdateTeamAction;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Users;

final class TeamUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.teams.manage', $model);

        $team = app(CreateTeamAction::class)->execute($model, $request->validate(CreateTeamAction::rules($model)));

        return redirect()
            ->route('atrium.roster.teams.show', [$model, $team->slug])
            ->with('status', __('roster::roster.team_created'));
    }

    public function show(string $organization, string $team): View
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.view', $model);

        $model = app(ShowTeamAction::class)->execute($model);

        /** @var OrganizationModel $owner */
        $owner = $model->organization;

        /** @var view-string $view */
        $view = 'roster::ui.teams.show';

        return view($view, [
            'team' => $model,
            'organization' => $owner,
            'candidates' => app(ListMembersAction::class)->execute($owner, ['per_page' => 100]),
            'directory' => $this->users,
        ]);
    }

    public function update(Request $request, string $organization, string $team): RedirectResponse
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.manage', $model);

        $model = app(UpdateTeamAction::class)->execute($model, $request->validate(UpdateTeamAction::rules($model)));

        return $this->backTo($model, 'roster::roster.team_updated');
    }

    public function destroy(string $organization, string $team): RedirectResponse
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.manage', $model->organization);

        app(DeleteTeamAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.show', [$organization, 'tab' => 'teams'])
            ->with('status', __('roster::roster.team_deleted'));
    }

    public function addMember(Request $request, string $organization, string $team): RedirectResponse
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.manage', $model);

        $model = app(AddTeamMemberAction::class)->execute($model, $request->validate(AddTeamMemberAction::rules()));

        return $this->backTo($model, 'roster::roster.team_member_added');
    }

    public function removeMember(string $organization, string $team, string $user): RedirectResponse
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.manage', $model);

        $model = app(RemoveTeamMemberAction::class)->execute($model, $this->users->findOrFail($user));

        return $this->backTo($model, 'roster::roster.team_member_removed');
    }

    private function organization(string $slug): OrganizationModel
    {
        return OrganizationModel::query()->where('slug', $slug)->firstOrFail();
    }

    private function team(string $organization, string $slug): TeamModel
    {
        return TeamModel::query()
            ->where('organization_id', $this->organization($organization)->getKey())
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function backTo(TeamModel $team, string $message): RedirectResponse
    {
        /** @var OrganizationModel $organization */
        $organization = $team->organization;

        return redirect()
            ->route('atrium.roster.teams.show', [$organization, $team->slug])
            ->with('status', __($message));
    }
}
