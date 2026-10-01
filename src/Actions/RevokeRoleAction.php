<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Actions\Concerns\GuardsEscalation;
use JayI\Roster\Events\Action\RoleRevokedActionEvent;
use JayI\Roster\Events\Action\RoleRevokingActionEvent;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;

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
    public function execute(RoleAssignment $assignment, ?Model $actor = null): void
    {
        $assignment->loadMissing(['role.permissions', 'organization', 'team']);

        /** @var Role $role */
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
