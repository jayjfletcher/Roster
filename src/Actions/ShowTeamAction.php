<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JayI\Roster\Events\Action\TeamShowingActionEvent;
use JayI\Roster\Events\Action\TeamShownActionEvent;
use JayI\Roster\Models\Team;

final class ShowTeamAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(Team $team): Team
    {
        TeamShowingActionEvent::dispatch($team);

        // Deleted users keep their seats for a restore, but aren't shown.
        $team->load(['organization', 'memberships' => fn (BelongsToMany $query): BelongsToMany => $query->whereHas('user')->with('user')])->loadCount('seats');

        TeamShownActionEvent::dispatch($team);

        return $team;
    }
}
