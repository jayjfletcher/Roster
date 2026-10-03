<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Mcp\Requests;

use JayI\Roster\Domains\Team\Actions\AddTeamMemberAction;
use JayI\Roster\Domains\Team\Models\TeamModel;
use Laravel\Mcp\ResponseFactory;

final class AddTeamMemberMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): TeamModel
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
