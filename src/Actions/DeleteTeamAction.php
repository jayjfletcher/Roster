<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Roster\Events\Action\TeamDeletedActionEvent;
use JayI\Roster\Events\Action\TeamDeletingActionEvent;
use JayI\Roster\Models\Profile;
use JayI\Roster\Models\Team;

final class DeleteTeamAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(Team $team): void
    {
        TeamDeletingActionEvent::dispatch($team);

        DB::transaction(function () use ($team): void {
            Profile::query()->where('current_team_id', $team->getKey())->update(['current_team_id' => null]);

            $team->seats()->delete();
            $team->delete();
        });

        TeamDeletedActionEvent::dispatch($team);
    }
}
