<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ShowTeamAction;
use JayI\Roster\Models\Team;
use Laravel\Mcp\ResponseFactory;

final class ShowTeamMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): Team
    {
        return $this->team();
    }

    protected function rules(): array
    {
        return ShowTeamAction::rules() + $this->teamRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithTeam(app(ShowTeamAction::class)->execute($this->team()));
    }
}
