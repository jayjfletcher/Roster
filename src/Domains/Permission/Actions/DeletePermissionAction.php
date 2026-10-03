<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Permission\Events\PermissionDeletedActionEvent;
use JayI\Roster\Domains\Permission\Events\PermissionDeletingActionEvent;
use JayI\Roster\Domains\Permission\Models\PermissionModel;
use JayI\Roster\Domains\Permission\Services\Permissions;

final class DeletePermissionAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(PermissionModel $permission): void
    {
        if ($permission->system) {
            throw ValidationException::withMessages(['permission' => __('roster::roster.cannot_delete_system')]);
        }

        PermissionDeletingActionEvent::dispatch($permission);

        DB::transaction(fn () => $permission->delete());

        app(Permissions::class)->flush();

        PermissionDeletedActionEvent::dispatch($permission);
    }
}
