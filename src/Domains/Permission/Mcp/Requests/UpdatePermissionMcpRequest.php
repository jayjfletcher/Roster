<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Permission\Actions\UpdatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Roster\Mcp\Request;

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
