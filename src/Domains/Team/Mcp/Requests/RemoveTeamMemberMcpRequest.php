<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Users;

final class RemoveTeamMemberMcpRequest extends TeamMcpRequest
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
        return RemoveTeamMemberAction::rules() + $this->teamRules() + ['user' => ['required']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithTeam(app(RemoveTeamMemberAction::class)->execute($this->team(), app(Users::class)->findOrFail($validated['user'])));
    }
}
