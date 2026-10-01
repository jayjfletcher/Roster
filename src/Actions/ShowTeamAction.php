<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

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

        $team->load(['organization', 'memberships.user'])->loadCount('seats');

        TeamShownActionEvent::dispatch($team);

        return $team;
    }
}
