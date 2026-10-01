<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ShowRoleAction;
use Laravel\Mcp\ResponseFactory;

final class ShowRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function rules(): array
    {
        return ShowRoleAction::rules() + $this->roleRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respondWithRole(app(ShowRoleAction::class)->execute($this->role()));
    }
}
