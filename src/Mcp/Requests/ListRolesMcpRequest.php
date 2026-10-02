<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ListRolesAction;
use JayI\Roster\Http\Resources\RoleResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\ResponseFactory;

final class ListRolesMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function scope(): Organization|Team|null
    {
        return Scopes::fromInput($this->get('organization'), $this->get('team'));
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return ListRolesAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $roles = app(ListRolesAction::class)->execute($validated, $this->organizations());

        return $this->structuredCollection(RoleResource::collection($roles->items())->resolve(), [
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
        ]);
    }
}
