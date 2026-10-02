<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Planners;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Actions\AssignRoleAction;
use JayI\Roster\Actions\Concerns\ManagesMemberships;
use JayI\Roster\Actions\CreateInvitationAction;
use JayI\Roster\Actions\CreateUserAction;
use JayI\Roster\Actions\UpdateProfileAction;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Enums\RoleScope;
use JayI\Roster\Events\Action\MemberAddedActionEvent;
use JayI\Roster\Events\Action\MemberAddingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Support\Users;
use RuntimeException;

/**
 * Members into an organization: `email, name, display_name, teams, role`.
 *
 * Accounts on the organization's domains are linked or created and join;
 * anyone else is invited. Teams and a role are added, never removed.
 */
final class MembersPlanner extends Planner
{
    use ManagesMemberships;

    public function __construct(private readonly Users $users) {}

    public function plan(array $values, Transfer $transfer, ?Model $actor): array
    {
        $organization = $this->organization($transfer);
        $email = strtolower($values['email'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->outcome(self::ERROR, __('roster::roster.import_invalid_email'));
        }

        [$teams, $unknownTeams] = $this->teams($transfer, $values['teams'] ?? '');

        if ($unknownTeams !== []) {
            return $this->outcome(self::ERROR, __('roster::roster.import_unknown_teams', ['teams' => implode(', ', $unknownTeams)]));
        }

        $role = null;

        if (($values['role'] ?? '') !== '') {
            $role = $this->role($transfer, $values['role']);

            if ($role === null) {
                return $this->outcome(self::ERROR, __('roster::roster.import_unknown_role', ['role' => $values['role']]));
            }

            if (! $this->mayGrant($transfer, $actor, $role)) {
                return $this->outcome(self::ERROR, __('roster::roster.import_role_not_grantable', ['role' => $role->slug]));
            }
        }

        $user = $this->users->findByEmail($email);

        if ($user !== null && $organization->membershipFor($user) !== null) {
            $newTeams = array_filter($teams, fn (Team $team): bool => ! $team->hasMember($user));
            $newRole = $role !== null && ! $this->holds($user, $role, $organization);

            return $newTeams === [] && ! $newRole
                ? $this->outcome(self::SKIP, __('roster::roster.import_already_member'))
                : $this->outcome(self::UPDATE, __('roster::roster.import_adds_teams_or_role'));
        }

        if ($this->owned($transfer, $email)) {
            return $user !== null
                ? $this->outcome(self::LINK, __('roster::roster.import_existing_account'))
                : $this->outcome(self::CREATE, __('roster::roster.import_new_account'));
        }

        if (! $this->remember($transfer, 'may-invite', fn (): bool => app(Authorizer::class)->check($actor, 'roster.invitations.manage', $organization))) {
            return $this->outcome(self::ERROR, __('roster::roster.import_cannot_invite'));
        }

        if ($organization->invitations()->pending()->where('email', $email)->exists()) {
            return $this->outcome(self::SKIP, __('roster::roster.invitation_already_pending'));
        }

        return $this->outcome(self::INVITE, __('roster::roster.import_outside_domains'));
    }

    public function apply(array $values, Transfer $transfer, ?Model $actor): array
    {
        $plan = $this->plan($values, $transfer, $actor);
        $organization = $this->organization($transfer);
        $email = strtolower($values['email'] ?? '');
        [$teams] = $this->teams($transfer, $values['teams'] ?? '');
        $role = ($values['role'] ?? '') !== '' ? $this->role($transfer, $values['role']) : null;

        if (in_array($plan['action'], [self::ERROR, self::SKIP], true)) {
            return $plan;
        }

        if ($plan['action'] === self::INVITE) {
            app(CreateInvitationAction::class)->execute($organization, [
                'email' => $email,
                'teams' => array_map(fn (Team $team): string => $team->slug, $teams),
            ], $actor);

            return $plan;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            $user = app(CreateUserAction::class)->execute([
                'name' => ($values['name'] ?? '') !== '' ? $values['name'] : Str::before($email, '@'),
                'email' => $email,
            ]);
        }

        if (($values['display_name'] ?? '') !== '') {
            app(UpdateProfileAction::class)->execute($user, ['display_name' => $values['display_name']]);
        }

        // AddMemberAction would record the membership as direct; join with
        // the import source but announce it the same way.
        if ($organization->membershipFor($user) === null) {
            MemberAddingActionEvent::dispatch($organization, $user);
            $this->join($organization, $user, MembershipSource::Import);
            MemberAddedActionEvent::dispatch($organization, $user);
        }

        foreach ($teams as $team) {
            if (! $team->hasMember($user)) {
                app(AddTeamMemberAction::class)->execute($team, ['user' => $user]);
            }
        }

        if ($role !== null && ! $this->holds($user, $role, $organization)) {
            app(AssignRoleAction::class)->execute($user, ['role' => $role->id, 'organization' => $organization->slug], $actor);
        }

        return $plan;
    }

    private function organization(Transfer $transfer): Organization
    {
        return $this->remember($transfer, 'organization', fn (): ?Organization => $transfer->organization)
            ?? throw new RuntimeException('A members import needs an organization.');
    }

    /**
     * Teams named by slug or name, from the organization's teams loaded once.
     *
     * @return array{0: array<int, Team>, 1: array<int, string>}
     */
    private function teams(Transfer $transfer, string $value): array
    {
        $teams = $this->remember($transfer, 'teams', fn (): Collection => $this->organization($transfer)->teams()->get());
        $found = [];
        $unknown = [];

        foreach ($this->list($value) as $name) {
            $team = $teams->first(fn (Team $team): bool => $team->slug === $name)
                ?? $teams->first(fn (Team $team): bool => strcasecmp($team->name, $name) === 0);

            $team === null ? $unknown[] = $name : $found[] = $team;
        }

        return [$found, $unknown];
    }

    /**
     * An organization role by slug: the organization's own before a shared one.
     */
    private function role(Transfer $transfer, string $slug): ?Role
    {
        $organization = $this->organization($transfer);

        return $this->remember($transfer, 'roles', fn (): Collection => Role::query()
            ->with('permissions')
            ->where('scope', RoleScope::Organization)
            ->where(fn (Builder $query): Builder => $query->whereNull('organization_id')->orWhere('organization_id', $organization->getKey()))
            ->orderByRaw('organization_id is null')
            ->get())
            ->firstWhere('slug', $slug);
    }

    /**
     * The same rule as assigning by hand: nobody hands out more than they hold.
     */
    private function mayGrant(Transfer $transfer, ?Model $actor, Role $role): bool
    {
        if ($actor === null || ! app(Authorizer::class)->enabled()) {
            return true;
        }

        $held = $this->remember($transfer, 'actor-permissions', fn (): array => app(Permissions::class)->for($actor, $this->organization($transfer)));

        return ! $role->super
            && array_diff($role->permissions->pluck('name')->all(), $held) === [];
    }

    private function holds(Model $user, Role $role, Organization $organization): bool
    {
        return RoleAssignment::query()
            ->where('role_id', $role->getKey())
            ->where('user_id', $user->getKey())
            ->where('organization_id', $organization->getKey())
            ->whereNull('team_id')
            ->exists();
    }

    private function owned(Transfer $transfer, string $email): bool
    {
        $domains = $this->remember($transfer, 'domains', fn (): array => array_map('strtolower', $this->organization($transfer)->domains()->pluck('domain')->all()));

        return in_array(strtolower(Str::after($email, '@')), $domains, true);
    }
}
