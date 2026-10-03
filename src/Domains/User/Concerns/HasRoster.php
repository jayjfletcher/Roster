<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use JayI\Roster\Domains\Organization\Models\MembershipModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Permission\Services\Permissions;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\User\Enums\UserStatus;
use JayI\Roster\Domains\User\Models\ProfileModel;
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
     * @return HasOne<ProfileModel, $this>
     */
    public function rosterProfile(): HasOne
    {
        return $this->hasOne(ProfileModel::class, 'user_id');
    }

    /**
     * @return HasMany<MembershipModel, $this>
     */
    public function rosterMemberships(): HasMany
    {
        return $this->hasMany(MembershipModel::class, 'user_id');
    }

    /**
     * @return BelongsToMany<OrganizationModel, $this>
     */
    public function rosterOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationModel::class, 'roster_memberships', 'user_id', 'organization_id')->withTimestamps();
    }

    /**
     * @return HasMany<RoleAssignmentModel, $this>
     */
    public function rosterRoleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignmentModel::class, 'user_id');
    }

    /**
     * Whether the user holds a Roster permission: globally, or in the given
     * organization or team. Prefer `$user->can()`, which also consults the
     * app's own gates and policies.
     */
    public function hasRosterPermission(string $permission, OrganizationModel|TeamModel|null $scope = null): bool
    {
        return app(Permissions::class)->allows($this, $permission, $scope);
    }

    /**
     * The organization the user is working in, falling back to their first.
     */
    public function currentOrganization(): ?OrganizationModel
    {
        return app(Roster::class)->organization($this);
    }

    /**
     * The team the user is working in, if any.
     */
    public function currentTeam(): ?TeamModel
    {
        return app(Roster::class)->team($this);
    }

    /**
     * The user's profile, created on first access.
     */
    public function roster(): ProfileModel
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
