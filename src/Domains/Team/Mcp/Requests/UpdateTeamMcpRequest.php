<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Mcp\Requests;

use JayI\Roster\Domains\Team\Actions\UpdateTeamAction;
use JayI\Roster\Domains\Team\Models\TeamModel;
use Laravel\Mcp\ResponseFactory;

final class UpdateTeamMcpRequest extends TeamMcpRequest
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
        return UpdateTeamAction::rules($this->team()) + $this->teamRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $data = $this->without($validated, 'organization', 'team');

        return $this->respondWithTeam(app(UpdateTeamAction::class)->execute($this->team(), $data));
    }
}
