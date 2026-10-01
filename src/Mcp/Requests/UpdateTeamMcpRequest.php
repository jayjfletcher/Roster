<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\UpdateTeamAction;
use JayI\Roster\Models\Team;
use Laravel\Mcp\ResponseFactory;

final class UpdateTeamMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): Team
    {
        return $this->team();
    }

    protected function rules(): array
    {
        return UpdateTeamAction::rules($this->team()) + $this->teamRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $data = $this->without($validated, 'organization', 'team');

        return $this->respondWithTeam(app(UpdateTeamAction::class)->execute($this->team(), $data));
    }
}
