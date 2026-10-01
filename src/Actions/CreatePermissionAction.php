<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Events\Action\PermissionCreatedActionEvent;
use JayI\Roster\Events\Action\PermissionCreatingActionEvent;
use JayI\Roster\Models\Permission;

final class CreatePermissionAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+(\.[a-z0-9_-]+)*$/', Rule::unique('roster_permissions', 'name')],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Declare an app permission, e.g. `invoices.edit`, for roles to grant.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Permission
    {
        PermissionCreatingActionEvent::dispatch($data);

        $permission = DB::transaction(fn (): Permission => Permission::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'system' => false,
        ]));

        app(Permissions::class)->flush();

        PermissionCreatedActionEvent::dispatch($permission);

        return $permission;
    }
}
