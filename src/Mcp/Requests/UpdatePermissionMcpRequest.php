<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\UpdatePermissionAction;
use JayI\Roster\Http\Resources\PermissionResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Permission;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class UpdatePermissionMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function rules(): array
    {
        return UpdatePermissionAction::rules() + ['name' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $permission = Permission::query()->where('name', $validated['name'])->firstOrFail();

        return Response::structured(['data' => (new PermissionResource(app(UpdatePermissionAction::class)->execute($permission, $validated)))->resolve()]);
    }
}
