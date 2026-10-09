<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionUpdatedActionEvent;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionUpdatingActionEvent;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

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
    public function execute(PermissionModel $permission, array $data): PermissionModel
    {
        PermissionUpdatingActionEvent::dispatch($permission, $data);

        DB::transaction(fn () => $permission->update(['description' => $data['description'] ?? null]));

        $permission = $permission->refresh();

        PermissionUpdatedActionEvent::dispatch($permission);

        return $permission;
    }
}
