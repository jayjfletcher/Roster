<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Roster\Domains\Team\Events\TeamDeletedActionEvent;
use JayI\Roster\Domains\Team\Events\TeamDeletingActionEvent;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\User\Models\ProfileModel;

final class DeleteTeamAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(TeamModel $team): void
    {
        TeamDeletingActionEvent::dispatch($team);

        DB::transaction(function () use ($team): void {
            ProfileModel::query()->where('current_team_id', $team->getKey())->update(['current_team_id' => null]);

            $team->seats()->delete();
            $team->delete();
        });

        TeamDeletedActionEvent::dispatch($team);
    }
}
