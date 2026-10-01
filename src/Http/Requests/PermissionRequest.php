<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use JayI\Roster\Http\Request;
use JayI\Roster\Models\Permission;

abstract class PermissionRequest extends Request
{
    private ?Permission $resolvedPermission = null;

    protected function permission(): Permission
    {
        return $this->resolvedPermission ??= Permission::query()->where('name', $this->route('permission'))->firstOrFail();
    }

    /**
     * A missing permission is a 404, not a validation error.
     */
    protected function prepareForValidation(): void
    {
        $this->permission();
    }
}
