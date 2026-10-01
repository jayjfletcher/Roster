<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\RemoveTeamMemberAction;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Users;
use Laravel\Mcp\ResponseFactory;

final class RemoveTeamMemberMcpRequest extends TeamMcpRequest
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
        return RemoveTeamMemberAction::rules() + $this->teamRules() + ['user' => ['required']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithTeam(app(RemoveTeamMemberAction::class)->execute($this->team(), app(Users::class)->findOrFail($validated['user'])));
    }
}
