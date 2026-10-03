<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Mcp\Requests;

use JayI\Roster\Domains\Permission\Actions\UpdatePermissionAction;
use JayI\Roster\Domains\Permission\Models\PermissionModel;
use JayI\Roster\Domains\Permission\Resources\PermissionResource;
use JayI\Roster\Mcp\Request;
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
        $permission = PermissionModel::query()->where('name', $validated['name'])->firstOrFail();

        return Response::structured(['data' => (new PermissionResource(app(UpdatePermissionAction::class)->execute($permission, $validated)))->resolve()]);
    }
}
