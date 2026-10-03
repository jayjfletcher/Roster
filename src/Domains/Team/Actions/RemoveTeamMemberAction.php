<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Organization\Concerns\ManagesMemberships;
use JayI\Roster\Domains\Team\Events\TeamMemberRemovedActionEvent;
use JayI\Roster\Domains\Team\Events\TeamMemberRemovingActionEvent;
use JayI\Roster\Domains\Team\Models\TeamMemberModel;
use JayI\Roster\Domains\Team\Models\TeamModel;

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

    public function execute(TeamModel $team, Model $user): TeamModel
    {
        $seat = TeamMemberModel::query()
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
