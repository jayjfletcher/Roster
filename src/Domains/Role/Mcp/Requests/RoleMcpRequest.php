<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Models\RoleModel;
use JayI\Roster\Domains\Role\Resources\RoleResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

abstract class RoleMcpRequest extends Request
{
    private ?RoleModel $resolvedRole = null;

    protected function role(): RoleModel
    {
        return $this->resolvedRole ??= RoleModel::query()->whereKey($this->get('role'))->firstOrFail();
    }

    /**
     * An organization's own role is managed within it; shared roles globally.
     */
    protected function scope(): ?OrganizationModel
    {
        return $this->role()->organization;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function roleRules(): array
    {
        return ['role' => ['required', 'string']];
    }

    protected function respondWithRole(RoleModel $role): ResponseFactory
    {
        return Response::structured(['data' => (new RoleResource($role))->resolve()]);
    }
}
