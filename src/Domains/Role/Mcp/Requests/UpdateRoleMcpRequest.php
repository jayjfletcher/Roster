<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Requests;

use JayI\Roster\Domains\Role\Actions\UpdateRoleAction;
use Laravel\Mcp\ResponseFactory;

final class UpdateRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function rules(): array
    {
        return UpdateRoleAction::rules() + $this->roleRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['role']);

        return $this->respondWithRole(app(UpdateRoleAction::class)->execute($this->role(), $validated, $this->actor()));
    }
}
