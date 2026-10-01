<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Models\Team;
use Laravel\Mcp\ResponseFactory;

final class AddTeamMemberMcpRequest extends TeamMcpRequest
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
        return AddTeamMemberAction::rules() + $this->teamRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithTeam(app(AddTeamMemberAction::class)->execute($this->team(), $this->without($validated, 'organization', 'team')));
    }
}
