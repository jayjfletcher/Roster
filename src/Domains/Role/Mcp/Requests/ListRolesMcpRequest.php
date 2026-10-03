<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Actions\ListRolesAction;
use JayI\Roster\Domains\Role\Resources\RoleResource;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Support\Scopes;
use Laravel\Mcp\ResponseFactory;

final class ListRolesMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function scope(): OrganizationModel|TeamModel|null
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
