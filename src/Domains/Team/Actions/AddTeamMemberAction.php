<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Events\TeamMemberAddedActionEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamMemberAddingActionEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Users;

final class AddTeamMemberAction
{
    use ManagesMemberships;

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'user' => ['required'],
        ];
    }

    /**
     * Seat an organization member on one of its teams.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(TeamModel $team, array $data): TeamModel
    {
        $user = $this->users->resolve($data['user'] ?? null);

        /** @var OrganizationModel $organization */
        $organization = $team->organization;

        $membership = $organization->membershipFor($user)
            ?? throw ValidationException::withMessages(['user' => __('roster::roster.not_a_member')]);

        if ($team->hasMember($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.already_on_team')]);
        }

        TeamMemberAddingActionEvent::dispatch($team, $user);

        DB::transaction(fn () => $this->seat($team, $membership));

        $team->load(['organization', 'memberships.user'])->loadCount('seats');

        TeamMemberAddedActionEvent::dispatch($team, $user);

        return $team;
    }
}
