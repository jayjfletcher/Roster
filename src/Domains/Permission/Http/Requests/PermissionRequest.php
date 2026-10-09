<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Http\Requests;

use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Http\Request;

abstract class PermissionRequest extends Request
{
    private ?PermissionModel $resolvedPermission = null;

    protected function permission(): PermissionModel
    {
        return $this->resolvedPermission ??= PermissionModel::query()->where('name', $this->route('permission'))->firstOrFail();
    }

    /**
     * A missing permission is a 404, not a validation error.
     */
    protected function prepareForValidation(): void
    {
        $this->permission();
    }
}
