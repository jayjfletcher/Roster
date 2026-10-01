<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Actions\Concerns\GuardsEscalation;
use JayI\Roster\Actions\Concerns\ResolvesScopes;
use JayI\Roster\Enums\RoleScope;
use JayI\Roster\Events\Action\RoleAssignedActionEvent;
use JayI\Roster\Events\Action\RoleAssigningActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;

final class AssignRoleAction
{
    use GuardsEscalation;
    use ResolvesScopes;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::exists('roster_roles', 'id')],
            'organization' => ['sometimes', 'nullable', 'string'],
            'team' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Give a user a role: a global role with no scope, an organization role
     * in an organization they belong to, or a team role on a team they sit on.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Model $user, array $data, ?Model $actor = null): RoleAssignment
    {
        $role = Role::query()->with('permissions')->whereKey($data['role'] ?? null)->firstOrFail();
        $organization = $this->organizationFrom($data['organization'] ?? null);
        $team = $this->teamFrom($organization, $data['team'] ?? null);

        $this->guardScope($user, $role, $organization, $team);
        $this->guardSuper($role, $actor);
        $this->guardEscalation($actor, $role->permissions->pluck('name')->all(), $team ?? $organization, 'role');

        $attributes = [
            'role_id' => $role->getKey(),
            'user_id' => $user->getKey(),
            'organization_id' => $organization?->getKey(),
            'team_id' => $team?->getKey(),
        ];

        if ($this->exists($attributes)) {
            throw ValidationException::withMessages(['role' => __('roster::roster.role_already_assigned')]);
        }

        RoleAssigningActionEvent::dispatch($user, $role, $data);

        $assignment = DB::transaction(fn (): RoleAssignment => RoleAssignment::query()->create($attributes));

        app(Permissions::class)->flush();

        $assignment->load(['role', 'user', 'organization', 'team']);

        RoleAssignedActionEvent::dispatch($assignment);

        return $assignment;
    }

    private function guardScope(Model $user, Role $role, ?Organization $organization, ?Team $team): void
    {
        $expected = match (true) {
            $team !== null => RoleScope::Team,
            $organization !== null => RoleScope::Organization,
            default => RoleScope::Global,
        };

        if ($role->scope !== $expected) {
            throw ValidationException::withMessages(['role' => __('roster::roster.role_scope_mismatch', ['scope' => $role->scope->label()])]);
        }

        if ($role->organization_id !== null && $role->organization_id !== $organization?->getKey()) {
            throw ValidationException::withMessages(['role' => __('roster::roster.role_belongs_elsewhere')]);
        }

        if ($organization !== null && $organization->membershipFor($user) === null) {
            throw ValidationException::withMessages(['user' => __('roster::roster.not_a_member')]);
        }

        if ($team !== null && ! $team->hasMember($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.not_on_team')]);
        }
    }

    private function guardSuper(Role $role, ?Model $actor): void
    {
        if (! $role->super || $actor === null || ! app(Authorizer::class)->enabled()) {
            return;
        }

        if (! app(Permissions::class)->isSuperAdmin($actor)) {
            throw ValidationException::withMessages(['role' => __('roster::roster.only_super_admins')]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function exists(array $attributes): bool
    {
        $query = RoleAssignment::query();

        foreach ($attributes as $column => $value) {
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        return $query->exists();
    }
}
