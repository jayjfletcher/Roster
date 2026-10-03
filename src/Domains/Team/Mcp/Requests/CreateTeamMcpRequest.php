<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Mcp\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Actions\CreateTeamAction;
use Laravel\Mcp\ResponseFactory;

final class CreateTeamMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): OrganizationModel
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
