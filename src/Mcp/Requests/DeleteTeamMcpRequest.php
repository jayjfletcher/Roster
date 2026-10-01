<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\DeleteTeamAction;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;

final class DeleteTeamMcpRequest extends TeamMcpRequest
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
        return DeleteTeamAction::rules() + $this->teamRules();
    }

    protected function handle(array $validated): Response
    {
        app(DeleteTeamAction::class)->execute($this->team());

        return Response::text('Team deleted.');
    }
}
