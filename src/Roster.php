<?php

declare(strict_types=1);

namespace JayI\Roster;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Audit\PendingAuditEntry;
use JayI\Roster\Audit\Surface;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\SsoConnection;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Users;

/**
 * Resolves a user's working context: the organization and team they have
 * switched to.
 */
class Roster
{
    public function __construct(private readonly Users $users) {}

    /**
     * The user's current organization.
     *
     * Falls back to the organization they joined first when none is stored,
     * or when they have since left the stored one. Null when they belong to
     * no organization.
     */
    public function organization(Model $user): ?Organization
    {
        $profile = $this->users->profileIfExists($user);
        $stored = $profile?->current_organization_id;

        $memberships = Membership::query()->where('user_id', $user->getKey());

        if ($stored !== null && (clone $memberships)->where('organization_id', $stored)->exists()) {
            return Organization::query()->find($stored);
        }

        $first = $memberships->oldest()->oldest('id')->first();

        return $first?->organization;
    }

    /**
     * Start one of the app's own audit entries, recorded as the signed-in
     * user unless `by()` says otherwise.
     */
    public function audit(string $action): PendingAuditEntry
    {
        return new PendingAuditEntry($action, app(Surface::class)->actor());
    }

    /**
     * The enforced SSO connection that must be used to sign in with this
     * email, if any. Super-admins are never forced, so nobody is locked out.
     */
    public function ssoRequiredFor(string $email): ?SsoConnection
    {
        $domain = strtolower(substr($email, (int) strrpos($email, '@') + 1));

        if (! str_contains($email, '@')) {
            return null;
        }

        $connection = SsoConnection::query()
            ->where('enforced', true)
            ->where('enabled', true)
            ->whereHas('organization.domains', fn (Builder $query): Builder => $query->where('domain', $domain))
            ->first();

        if ($connection === null) {
            return null;
        }

        $column = $this->users->column('email');
        $user = $column === null ? null : $this->users->query()->whereLike($column, $email)->get()
            ->first(fn (Model $candidate): bool => strcasecmp((string) $this->users->email($candidate), $email) === 0);

        return $user !== null && app(Permissions::class)->isSuperAdmin($user) ? null : $connection;
    }

    /**
     * The user's current team: the stored one, while it belongs to the
     * current organization and the user still holds a seat on it.
     */
    public function team(Model $user): ?Team
    {
        $organization = $this->organization($user);
        $stored = $this->users->profileIfExists($user)?->current_team_id;

        if ($organization === null || $stored === null) {
            return null;
        }

        $team = Team::query()->whereKey($stored)->where('organization_id', $organization->getKey())->first();

        return $team !== null && $team->hasMember($user) ? $team : null;
    }
}
