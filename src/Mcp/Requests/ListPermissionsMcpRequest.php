<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ListPermissionsAction;
use JayI\Roster\Http\Resources\PermissionResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class ListPermissionsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function rules(): array
    {
        return ListPermissionsAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $permissions = app(ListPermissionsAction::class)->execute($validated);

        return $this->structuredCollection(PermissionResource::collection($permissions->items())->resolve(), [
            'meta' => [
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
            ],
        ]);
    }
}
