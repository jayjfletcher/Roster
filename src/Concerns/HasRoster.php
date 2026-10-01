<?php

declare(strict_types=1);

namespace JayI\Roster\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Profile;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;
use JayI\Roster\Roster;
use JayI\Roster\Support\Users;

/**
 * Typed Roster helpers for the host user model.
 *
 * Optional: without the trait Roster registers the same `rosterProfile`,
 * `rosterMemberships`, `rosterOrganizations` and `rosterRoleAssignments`
 * relations on the configured
 * model dynamically.
 *
 * @phpstan-require-extends Model
 */
trait HasRoster
{
    /**
     * @return HasOne<Profile, $this>
     */
    public function rosterProfile(): HasOne
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function rosterMemberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * @return BelongsToMany<Organization, $this>
     */
    public function rosterOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'roster_memberships', 'user_id', 'organization_id')->withTimestamps();
    }

    /**
     * @return HasMany<RoleAssignment, $this>
     */
    public function rosterRoleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class, 'user_id');
    }

    /**
     * Whether the user holds a Roster permission: globally, or in the given
     * organization or team. Prefer `$user->can()`, which also consults the
     * app's own gates and policies.
     */
    public function hasRosterPermission(string $permission, Organization|Team|null $scope = null): bool
    {
        return app(Permissions::class)->allows($this, $permission, $scope);
    }

    /**
     * The organization the user is working in, falling back to their first.
     */
    public function currentOrganization(): ?Organization
    {
        return app(Roster::class)->organization($this);
    }

    /**
     * The team the user is working in, if any.
     */
    public function currentTeam(): ?Team
    {
        return app(Roster::class)->team($this);
    }

    /**
     * The user's profile, created on first access.
     */
    public function roster(): Profile
    {
        return app(Users::class)->profile($this);
    }

    public function rosterStatus(): UserStatus
    {
        return app(Users::class)->status($this);
    }

    public function isRosterActive(): bool
    {
        return $this->rosterStatus() === UserStatus::Active;
    }
}
