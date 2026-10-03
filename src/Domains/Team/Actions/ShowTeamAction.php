<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Actions;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JayI\Roster\Domains\Team\Events\TeamShowingActionEvent;
use JayI\Roster\Domains\Team\Events\TeamShownActionEvent;
use JayI\Roster\Domains\Team\Models\TeamModel;

final class ShowTeamAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(TeamModel $team): TeamModel
    {
        TeamShowingActionEvent::dispatch($team);

        // Deleted users keep their seats for a restore, but aren't shown.
        $team->load(['organization', 'memberships' => fn (BelongsToMany $query): BelongsToMany => $query->whereHas('user')->with('user')])->loadCount('seats');

        TeamShownActionEvent::dispatch($team);

        return $team;
    }
}
