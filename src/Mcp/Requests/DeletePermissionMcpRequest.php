<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\DeletePermissionAction;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Permission;
use Laravel\Mcp\Response;

final class DeletePermissionMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function rules(): array
    {
        return DeletePermissionAction::rules() + ['name' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        $permission = Permission::query()->where('name', $validated['name'])->firstOrFail();

        app(DeletePermissionAction::class)->execute($permission);

        return Response::text('Permission deleted.');
    }
}
