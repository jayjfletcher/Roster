<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\DeleteRoleAction;
use Laravel\Mcp\Response;

final class DeleteRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function rules(): array
    {
        return DeleteRoleAction::rules() + $this->roleRules();
    }

    protected function handle(array $validated): Response
    {
        app(DeleteRoleAction::class)->execute($this->role());

        return Response::text('Role deleted.');
    }
}
