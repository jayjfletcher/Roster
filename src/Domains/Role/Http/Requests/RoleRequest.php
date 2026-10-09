<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Http\Requests;

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Http\Request;

abstract class RoleRequest extends Request
{
    private ?RoleModel $resolvedRole = null;

    protected function role(): RoleModel
    {
        return $this->resolvedRole ??= RoleModel::query()->whereKey($this->route('role'))->firstOrFail();
    }

    /**
     * An organization's own role is managed within it; shared roles globally.
     */
    protected function scope(): ?OrganizationModel
    {
        return $this->role()->organization;
    }
}
