<?php

declare(strict_types=1);

namespace JayI\Roster\Actions\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Enums\RoleScope;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Profile;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;
use JayI\Roster\Models\TeamMember;

/**
 * Joining and leaving organizations and teams, shared by every Action that
 * changes membership.
 */
trait ManagesMemberships
{
    /**
     * Make the user a member. A new member gets the default member role from
     * `roster.roles.default_member`; owners need none.
     */
    private function join(Organization $organization, Model $user, MembershipSource $source): Membership
    {
        $membership = Membership::query()->firstOrCreate(
            ['organization_id' => $organization->getKey(), 'user_id' => $user->getKey()],
            ['source' => $source],
        );

        if ($membership->wasRecentlyCreated && ! $organization->isOwnedBy($user)) {
            $this->assignDefaultRole($organization, $user);
        }

        return $membership;
    }

    private function assignDefaultRole(Organization $organization, Model $user): void
    {
        $slug = config('roster.roles.default_member');

        if (! is_string($slug) || $slug === '') {
            return;
        }

        $role = Role::query()
            ->where('scope', RoleScope::Organization)
            ->where('slug', $slug)
            ->where(fn (Builder $query): Builder => $query->whereNull('organization_id')->orWhere('organization_id', $organization->getKey()))
            ->orderByRaw('organization_id is null')
            ->first();

        if ($role !== null) {
            RoleAssignment::query()->firstOrCreate([
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
                'organization_id' => $organization->getKey(),
                'team_id' => null,
            ]);

            app(Permissions::class)->flush();
        }
    }

    /**
     * Drop the roles a user held in an organization (and its teams), or on
     * one team.
     */
    private function revokeRoles(Model $user, ?Organization $organization = null, ?Team $team = null): void
    {
        $query = RoleAssignment::query()->where('user_id', $user->getKey());

        if ($team !== null) {
            $query->where('team_id', $team->getKey());
        } elseif ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        $query->delete();

        app(Permissions::class)->flush();
    }

    private function seat(Team $team, Membership $membership): void
    {
        TeamMember::query()->firstOrCreate([
            'team_id' => $team->getKey(),
            'membership_id' => $membership->getKey(),
        ]);
    }

    /**
     * Clear a user's stored context where it points at something they can
     * no longer use.
     */
    private function forgetContext(Model $user, ?Organization $organization = null, ?Team $team = null): void
    {
        $profile = Profile::query()->where('user_id', $user->getKey())->first();

        if ($profile === null) {
            return;
        }

        if ($organization !== null && $profile->current_organization_id === $organization->getKey()) {
            $profile->update(['current_organization_id' => null, 'current_team_id' => null]);
        }

        if ($team !== null && $profile->current_team_id === $team->getKey()) {
            $profile->update(['current_team_id' => null]);
        }
    }
}
