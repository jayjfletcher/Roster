<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Mcp\Requests;

use JayI\Roster\Domains\Permission\Actions\DeletePermissionAction;
use JayI\Roster\Domains\Permission\Models\PermissionModel;
use JayI\Roster\Mcp\Request;
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
        $permission = PermissionModel::query()->where('name', $validated['name'])->firstOrFail();

        app(DeletePermissionAction::class)->execute($permission);

        return Response::text('Permission deleted.');
    }
}
