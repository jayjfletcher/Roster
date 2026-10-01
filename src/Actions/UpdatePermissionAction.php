<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Roster\Events\Action\PermissionUpdatedActionEvent;
use JayI\Roster\Events\Action\PermissionUpdatingActionEvent;
use JayI\Roster\Models\Permission;

final class UpdatePermissionAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'description' => ['present', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Change a permission's description. Names are permanent: code checks them.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Permission $permission, array $data): Permission
    {
        PermissionUpdatingActionEvent::dispatch($permission, $data);

        DB::transaction(fn () => $permission->update(['description' => $data['description'] ?? null]));

        $permission = $permission->refresh();

        PermissionUpdatedActionEvent::dispatch($permission);

        return $permission;
    }
}
