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
use JayI\Roster\Models\TeamMember;
use JayI\Roster\Support\Users;

/**
 * Resolves a user's working context: the organization and team they have
 * switched to.
 */
class Roster
{
    /**
     * Current organization and team per user, for this request. Cleared by
     * `forget()` whenever an Action changes something.
     *
     * @var array<string, Organization|null>
     */
    private array $organizations = [];

    /** @var array<string, Team|null> */
    private array $teams = [];

    public function __construct(private readonly Users $users) {}

    /**
     * Drop the remembered current organizations and teams.
     */
    public function forget(): void
    {
        $this->organizations = [];
        $this->teams = [];
    }

    /**
     * The user's current organization.
     *
     * Falls back to the organization they joined first when none is stored,
     * or when they have since left the stored one. Null when they belong to
     * no organization.
     */
    public function organization(Model $user): ?Organization
    {
        $key = $this->key($user);

        return array_key_exists($key, $this->organizations)
            ? $this->organizations[$key]
            : $this->organizations[$key] = $this->resolveOrganization($user);
    }

    private function resolveOrganization(Model $user): ?Organization
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

        $user = $this->users->findByEmail($email);

        return $user !== null && app(Permissions::class)->isSuperAdmin($user) ? null : $connection;
    }

    /**
     * The user's current team: the stored one, while it belongs to the
     * current organization and the user still holds a seat on it.
     */
    public function team(Model $user): ?Team
    {
        $key = $this->key($user);

        return array_key_exists($key, $this->teams)
            ? $this->teams[$key]
            : $this->teams[$key] = $this->resolveTeam($user);
    }

    private function resolveTeam(Model $user): ?Team
    {
        $organization = $this->organization($user);
        $stored = $this->users->profileIfExists($user)?->current_team_id;

        if ($organization === null || $stored === null) {
            return null;
        }

        $team = Team::query()->whereKey($stored)->where('organization_id', $organization->getKey())->first();

        // Permission checks in a team scope need its organization; it's this one.
        $team?->setRelation('organization', $organization);

        return $team !== null && $team->hasMember($user) ? $team : null;
    }

    /**
     * Resolve the current organization and team of many users at once - a
     * page of users - in two queries, so per-user lookups after it are free.
     * Gives the same answers as `organization()` and `team()`.
     *
     * @param  iterable<int, Model>  $users
     */
    public function preload(iterable $users): void
    {
        $users = collect($users)->reject(fn (Model $user): bool => array_key_exists($this->key($user), $this->teams));

        if ($users->isEmpty()) {
            return;
        }

        $memberships = Membership::query()
            ->with('organization')
            ->whereIn('user_id', $users->map(fn (Model $user): mixed => $user->getKey())->all())
            ->oldest()
            ->oldest('id')
            ->get()
            ->groupBy(fn (Membership $membership): string => (string) $membership->user_id);

        $current = [];

        foreach ($users as $user) {
            $mine = $memberships->get((string) $user->getKey(), collect());
            $profile = $this->users->profileIfExists($user);
            $membership = $mine->firstWhere('organization_id', $profile?->current_organization_id) ?? $mine->first();

            $this->organizations[$this->key($user)] = $membership?->organization;
            $current[$this->key($user)] = [$user, $membership, $profile?->current_team_id];
        }

        $teamIds = array_values(array_filter(array_column($current, 2)));
        $teams = $teamIds === [] ? collect() : Team::query()->whereIn('id', $teamIds)->get()->keyBy('id');
        $seats = $teamIds === [] ? collect() : TeamMember::query()
            ->whereIn('team_id', $teamIds)
            ->whereIn('membership_id', array_filter(array_map(fn (array $entry): mixed => $entry[1]?->getKey(), $current)))
            ->get(['team_id', 'membership_id'])
            ->map(fn (TeamMember $seat): string => $seat->team_id.':'.$seat->membership_id);

        foreach ($current as $key => [$user, $membership, $teamId]) {
            $team = $teamId === null ? null : $teams->get($teamId);
            $seated = $team instanceof Team && $membership !== null
                && $team->organization_id === $membership->organization_id
                && $seats->contains($team->id.':'.$membership->getKey());

            if ($seated) {
                $team->setRelation('organization', $membership->organization);
            }

            $this->teams[$key] = $seated ? $team : null;
        }
    }

    private function key(Model $user): string
    {
        return $user->getMorphClass().':'.$user->getKey();
    }
}
