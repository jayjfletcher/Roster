<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use JayI\Roster\Events\Action\RoleShowingActionEvent;
use JayI\Roster\Events\Action\RoleShownActionEvent;
use JayI\Roster\Models\Role;

final class ShowRoleAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(Role $role): Role
    {
        RoleShowingActionEvent::dispatch($role);

        $role->load(['permissions', 'organization']);

        RoleShownActionEvent::dispatch($role);

        return $role;
    }
}
