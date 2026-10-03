<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Actions;

use JayI\Roster\Domains\Role\Events\RoleShowingActionEvent;
use JayI\Roster\Domains\Role\Events\RoleShownActionEvent;
use JayI\Roster\Domains\Role\Models\RoleModel;

final class ShowRoleAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(RoleModel $role): RoleModel
    {
        RoleShowingActionEvent::dispatch($role);

        $role->load(['permissions', 'organization']);

        RoleShownActionEvent::dispatch($role);

        return $role;
    }
}
