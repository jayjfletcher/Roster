<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Permission\Services\Authorizer;
use JayI\Roster\Domains\Permission\Services\Permissions;
use JayI\Roster\Domains\Role\Concerns\GuardsEscalation;
use JayI\Roster\Domains\Role\Events\RoleRevokedActionEvent;
use JayI\Roster\Domains\Role\Events\RoleRevokingActionEvent;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Role\Models\RoleModel;

final class RevokeRoleAction
{
    use GuardsEscalation;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Take a role away. Like granting, revoking needs the actor to hold the
     * role's permissions in that scope - no one can strip a super-admin.
     */
    public function execute(RoleAssignmentModel $assignment, ?Model $actor = null): void
    {
        $assignment->loadMissing(['role.permissions', 'organization', 'team']);

        /** @var RoleModel $role */
        $role = $assignment->role;

        if ($role->super && $actor !== null && app(Authorizer::class)->enabled() && ! app(Permissions::class)->isSuperAdmin($actor)) {
            throw ValidationException::withMessages(['role' => __('roster::roster.only_super_admins')]);
        }

        $this->guardEscalation($actor, $role->permissions->pluck('name')->all(), $assignment->team ?? $assignment->organization, 'role');

        RoleRevokingActionEvent::dispatch($assignment);

        DB::transaction(fn () => $assignment->delete());

        app(Permissions::class)->flush();

        RoleRevokedActionEvent::dispatch($assignment);
    }
}
