<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Http\Resources\RoleResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Role;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

abstract class RoleMcpRequest extends Request
{
    private ?Role $resolvedRole = null;

    protected function role(): Role
    {
        return $this->resolvedRole ??= Role::query()->whereKey($this->get('role'))->firstOrFail();
    }

    /**
     * An organization's own role is managed within it; shared roles globally.
     */
    protected function scope(): ?Organization
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

    protected function respondWithRole(Role $role): ResponseFactory
    {
        return Response::structured(['data' => (new RoleResource($role))->resolve()]);
    }
}
