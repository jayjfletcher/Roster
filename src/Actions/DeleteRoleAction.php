<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Events\Action\RoleDeletedActionEvent;
use JayI\Roster\Events\Action\RoleDeletingActionEvent;
use JayI\Roster\Models\Role;

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
    public function execute(Role $role): void
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
