<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\Concerns\ManagesMemberships;
use JayI\Roster\Events\Action\TeamMemberAddedActionEvent;
use JayI\Roster\Events\Action\TeamMemberAddingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Users;

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
    public function execute(Team $team, array $data): Team
    {
        $user = $this->users->findOrFail($data['user'] ?? null);

        /** @var Organization $organization */
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
