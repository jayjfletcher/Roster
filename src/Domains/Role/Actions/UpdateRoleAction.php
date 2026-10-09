<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Concerns\GuardsEscalation;
use RefactorCircus\Roster\Domains\Role\Events\RoleUpdatedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleUpdatingActionEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

final class UpdateRoleAction
{
    use GuardsEscalation;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('roster_permissions', 'name')],
        ];
    }

    /**
     * Rename a role or replace its permissions. A given `permissions` list
     * replaces the old one; newly added permissions must be held by the actor.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(RoleModel $role, array $data, ?Model $actor = null): RoleModel
    {
        if (array_key_exists('permissions', $data)) {
            $current = $role->permissions()->pluck('name')->all();
            $added = array_values(array_diff(array_map('strval', (array) $data['permissions']), $current));

            $this->guardEscalation($actor, $added, $role->organization, 'permissions');
        }

        RoleUpdatingActionEvent::dispatch($role, $data);

        DB::transaction(function () use ($role, $data): void {
            $role->update(array_intersect_key($data, array_flip(['name', 'description'])));

            if (array_key_exists('permissions', $data)) {
                $role->permissions()->sync(PermissionModel::query()->whereIn('name', (array) $data['permissions'])->pluck('id'));
            }
        });

        app(Permissions::class)->flush();

        $role = $role->refresh()->load(['permissions', 'organization']);

        RoleUpdatedActionEvent::dispatch($role);

        return $role;
    }
}
