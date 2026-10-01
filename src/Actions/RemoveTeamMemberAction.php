<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\Concerns\ManagesMemberships;
use JayI\Roster\Events\Action\TeamMemberRemovedActionEvent;
use JayI\Roster\Events\Action\TeamMemberRemovingActionEvent;
use JayI\Roster\Models\Team;
use JayI\Roster\Models\TeamMember;

final class RemoveTeamMemberAction
{
    use ManagesMemberships;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(Team $team, Model $user): Team
    {
        $seat = TeamMember::query()
            ->where('team_id', $team->getKey())
            ->whereHas('membership', fn (Builder $query): Builder => $query->where('user_id', $user->getKey()))
            ->first()
            ?? throw ValidationException::withMessages(['user' => __('roster::roster.not_on_team')]);

        TeamMemberRemovingActionEvent::dispatch($team, $user);

        DB::transaction(function () use ($seat, $user, $team): void {
            $seat->delete();
            $this->revokeRoles($user, team: $team);
            $this->forgetContext($user, team: $team);
        });

        $team->load(['organization', 'memberships.user'])->loadCount('seats');

        TeamMemberRemovedActionEvent::dispatch($team, $user);

        return $team;
    }
}
