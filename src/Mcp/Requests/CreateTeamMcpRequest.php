<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\ResponseFactory;

final class CreateTeamMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return CreateTeamAction::rules($this->organization()) + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithTeam(app(CreateTeamAction::class)->execute($this->organization(), $this->without($validated, 'organization')));
    }
}
