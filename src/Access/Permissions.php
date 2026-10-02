<?php

declare(strict_types=1);

namespace JayI\Roster\Access;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Permission;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;
use JayI\Roster\Roster;
use JayI\Roster\Support\Users;

/**
 * Resolves what a user may do.
 *
 * A user's permissions in a scope are the union of their global roles, their
 * roles in the scope's organization and, for a team, their roles on that
 * team. An organization's owner holds every permission within it, except the
 * global-only ones. A super-admin passes everything.
 *
 * Bound per request: results are memoized and must not outlive it.
 */
final class Permissions
{
    /**
     * Permissions that only make sense globally: an organization owner does
     * not get them by owning an organization.
     *
     * @var array<int, string>
     */
    public const array GLOBAL_ONLY_PREFIXES = ['roster.users.', 'roster.organizations.create', 'atrium.'];

    /** @var array<string, array<int, string>> */
    private array $resolved = [];

    /** @var array<string, bool> */
    private array $supers = [];

    /** @var array<int, string>|null */
    private ?array $known = null;

    public function __construct(
        private readonly Users $users,
        private readonly Repository $config,
    ) {}

    /**
     * Whether a permission with this name exists.
     */
    public function knows(string $permission): bool
    {
        return in_array($permission, $this->all(), true);
    }

    /**
     * Every permission name.
     *
     * @return array<int, string>
     */
    public function all(): array
    {
        return $this->known ??= Permission::query()->orderBy('name')->pluck('name')->all();
    }

    public function allows(Model $user, string $permission, Organization|Team|null $scope = null): bool
    {
        return in_array($permission, $this->for($user, $scope), true);
    }

    /**
     * The user's permissions in a scope: global only when `$scope` is null.
     *
     * @return array<int, string>
     */
    public function for(Model $user, Organization|Team|null $scope = null): array
    {
        $key = $user->getMorphClass().':'.$user->getKey().':'.($scope === null ? 'global' : $scope::class.':'.$scope->getKey());

        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        if ($this->isSuperAdmin($user)) {
            return $this->resolved[$key] = $this->all();
        }

        $organization = $scope instanceof Team ? $scope->organization : $scope;
        $team = $scope instanceof Team ? $scope : null;

        $names = RoleAssignment::query()
            ->where('user_id', $user->getKey())
            ->where(function (Builder $query) use ($organization, $team): void {
                $query->where(fn (Builder $global): Builder => $global->whereNull('organization_id')->whereNull('team_id'));

                if ($organization !== null) {
                    $query->orWhere(fn (Builder $org): Builder => $org->where('organization_id', $organization->getKey())->whereNull('team_id'));
                }

                if ($team !== null) {
                    $query->orWhere('team_id', $team->getKey());
                }
            })
            ->join('roster_permission_role', 'roster_permission_role.role_id', '=', 'roster_role_assignments.role_id')
            ->join('roster_permissions', 'roster_permissions.id', '=', 'roster_permission_role.permission_id')
            ->distinct()
            ->pluck('roster_permissions.name')
            ->all();

        if ($organization !== null && $organization->isOwnedBy($user)) {
            $names = array_merge($names, array_values(array_filter($this->all(), fn (string $name): bool => ! $this->isGlobalOnly($name))));
        }

        $names = array_values(array_unique(array_map('strval', $names)));
        sort($names);

        return $this->resolved[$key] = $names;
    }

    /**
     * The organizations a user holds a permission in, through an
     * organization role or by owning the organization. Null when they hold it
     * globally, so no organization limits them.
     *
     * @return array<int, int|string>|null
     */
    public function organizationsWith(Model $user, string $permission): ?array
    {
        if (in_array($permission, $this->for($user), true)) {
            return null;
        }

        $assigned = RoleAssignment::query()
            ->where('user_id', $user->getKey())
            ->whereNotNull('organization_id')
            ->whereNull('team_id')
            ->whereHas('role.permissions', fn (Builder $query): Builder => $query->where('name', $permission))
            ->pluck('organization_id')
            ->all();

        $owned = $this->isGlobalOnly($permission)
            ? []
            : Organization::query()->where('owner_id', $user->getKey())->pluck('id')->all();

        return array_values(array_unique([...$assigned, ...$owned]));
    }

    /**
     * Super-admins hold a super role, or are listed in `roster.super_admins`
     * with a verified email - an unverified address proves nothing.
     */
    public function isSuperAdmin(Model $user): bool
    {
        $key = $user->getMorphClass().':'.$user->getKey();

        return $this->supers[$key] ??= $this->hasSuperRole($user) || $this->isConfiguredSuperAdmin($user);
    }

    /**
     * The scope a Gate check without an explicit organization or team uses:
     * the user's current team, else their current organization.
     */
    public function contextScope(Model $user): Organization|Team|null
    {
        $roster = app(Roster::class);

        return $roster->team($user) ?? $roster->organization($user);
    }

    public function flush(): void
    {
        $this->resolved = [];
        $this->supers = [];
        $this->known = null;
    }

    private function hasSuperRole(Model $user): bool
    {
        return RoleAssignment::query()
            ->where('user_id', $user->getKey())
            ->whereNull('organization_id')
            ->whereNull('team_id')
            ->whereHas('role', fn (Builder $role): Builder => $role->where('super', true))
            ->exists();
    }

    private function isConfiguredSuperAdmin(Model $user): bool
    {
        $emails = array_map(
            fn (mixed $email): string => strtolower((string) $email),
            (array) $this->config->get('roster.super_admins', []),
        );

        $email = $this->users->email($user);

        return $email !== null
            && in_array(strtolower($email), $emails, true)
            && $this->users->emailVerified($user);
    }

    private function isGlobalOnly(string $permission): bool
    {
        foreach (self::GLOBAL_ONLY_PREFIXES as $prefix) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
