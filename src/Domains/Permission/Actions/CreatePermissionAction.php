<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Domains\Permission\Events\PermissionCreatedActionEvent;
use JayI\Roster\Domains\Permission\Events\PermissionCreatingActionEvent;
use JayI\Roster\Domains\Permission\Models\PermissionModel;
use JayI\Roster\Domains\Permission\Services\Permissions;

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
    public function execute(array $data): PermissionModel
    {
        PermissionCreatingActionEvent::dispatch($data);

        $permission = DB::transaction(fn (): PermissionModel => PermissionModel::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'system' => false,
        ]));

        app(Permissions::class)->flush();

        PermissionCreatedActionEvent::dispatch($permission);

        return $permission;
    }
}
