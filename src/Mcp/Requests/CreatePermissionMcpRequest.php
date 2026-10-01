<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\CreatePermissionAction;
use JayI\Roster\Http\Resources\PermissionResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreatePermissionMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function rules(): array
    {
        return CreatePermissionAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => (new PermissionResource(app(CreatePermissionAction::class)->execute($validated)))->resolve()]);
    }
}
