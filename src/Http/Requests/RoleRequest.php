<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use JayI\Roster\Http\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Role;

abstract class RoleRequest extends Request
{
    private ?Role $resolvedRole = null;

    protected function role(): Role
    {
        return $this->resolvedRole ??= Role::query()->whereKey($this->route('role'))->firstOrFail();
    }

    /**
     * An organization's own role is managed within it; shared roles globally.
     */
    protected function scope(): ?Organization
    {
        return $this->role()->organization;
    }
}
