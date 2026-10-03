<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Permission\Services\Permissions;
use JayI\Roster\Domains\Role\Events\RoleDeletedActionEvent;
use JayI\Roster\Domains\Role\Events\RoleDeletingActionEvent;
use JayI\Roster\Domains\Role\Models\RoleModel;

final class DeleteRoleAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Delete a role and every assignment of it. Built-in roles stay.
     */
    public function execute(RoleModel $role): void
    {
        if ($role->system) {
            throw ValidationException::withMessages(['role' => __('roster::roster.cannot_delete_system')]);
        }

        RoleDeletingActionEvent::dispatch($role);

        DB::transaction(fn () => $role->delete());

        app(Permissions::class)->flush();

        RoleDeletedActionEvent::dispatch($role);
    }
}
