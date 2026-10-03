<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Mcp\Requests;

use JayI\Roster\Domains\Team\Actions\ShowTeamAction;
use JayI\Roster\Domains\Team\Models\TeamModel;
use Laravel\Mcp\ResponseFactory;

final class ShowTeamMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): TeamModel
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
