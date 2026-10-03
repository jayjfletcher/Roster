<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Mcp\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Actions\DeleteTeamAction;
use Laravel\Mcp\Response;

final class DeleteTeamMcpRequest extends TeamMcpRequest
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
        return DeleteTeamAction::rules() + $this->teamRules();
    }

    protected function handle(array $validated): Response
    {
        app(DeleteTeamAction::class)->execute($this->team());

        return Response::text('Team deleted.');
    }
}
