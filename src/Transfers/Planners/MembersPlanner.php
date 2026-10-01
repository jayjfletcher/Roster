<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Planners;

use Illuminate\Database\Eloquent\Builder;
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

        [$teams, $unknownTeams] = $this->teams($organization, $values['teams'] ?? '');

        if ($unknownTeams !== []) {
            return $this->outcome(self::ERROR, __('roster::roster.import_unknown_teams', ['teams' => implode(', ', $unknownTeams)]));
        }

        $role = null;

        if (($values['role'] ?? '') !== '') {
            $role = $this->role($organization, $values['role']);

            if ($role === null) {
                return $this->outcome(self::ERROR, __('roster::roster.import_unknown_role', ['role' => $values['role']]));
            }

            if (! $this->mayGrant($actor, $role, $organization)) {
                return $this->outcome(self::ERROR, __('roster::roster.import_role_not_grantable', ['role' => $role->slug]));
            }
        }

        $user = $this->find($email);

        if ($user !== null && $organization->membershipFor($user) !== null) {
            $newTeams = array_filter($teams, fn (Team $team): bool => ! $team->hasMember($user));
            $newRole = $role !== null && ! $this->holds($user, $role, $organization);

            return $newTeams === [] && ! $newRole
                ? $this->outcome(self::SKIP, __('roster::roster.import_already_member'))
                : $this->outcome(self::UPDATE, __('roster::roster.import_adds_teams_or_role'));
        }

        if ($this->owned($organization, $email)) {
            return $user !== null
                ? $this->outcome(self::LINK, __('roster::roster.import_existing_account'))
                : $this->outcome(self::CREATE, __('roster::roster.import_new_account'));
        }

        if (! app(Authorizer::class)->check($actor, 'roster.invitations.manage', $organization)) {
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
        [$teams] = $this->teams($organization, $values['teams'] ?? '');
        $role = ($values['role'] ?? '') !== '' ? $this->role($organization, $values['role']) : null;

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

        $user = $this->find($email);

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
                app(AddTeamMemberAction::class)->execute($team, ['user' => $user->getRouteKey()]);
            }
        }

        if ($role !== null && ! $this->holds($user, $role, $organization)) {
            app(AssignRoleAction::class)->execute($user, ['role' => $role->id, 'organization' => $organization->slug], $actor);
        }

        return $plan;
    }

    private function organization(Transfer $transfer): Organization
    {
        return $transfer->organization ?? throw new RuntimeException('A members import needs an organization.');
    }

    /**
     * @return array{0: array<int, Team>, 1: array<int, string>}
     */
    private function teams(Organization $organization, string $value): array
    {
        $found = [];
        $unknown = [];

        foreach ($this->list($value) as $name) {
            $team = $organization->teams()
                ->where(fn (Builder $query): Builder => $query->where('slug', $name)->orWhere('name', $name))
                ->first();

            $team === null ? $unknown[] = $name : $found[] = $team;
        }

        return [$found, $unknown];
    }

    private function role(Organization $organization, string $slug): ?Role
    {
        return Role::query()
            ->where('scope', RoleScope::Organization)
            ->where('slug', $slug)
            ->where(fn (Builder $query): Builder => $query->whereNull('organization_id')->orWhere('organization_id', $organization->getKey()))
            ->orderByRaw('organization_id is null')
            ->first();
    }

    /**
     * The same rule as assigning by hand: nobody hands out more than they hold.
     */
    private function mayGrant(?Model $actor, Role $role, Organization $organization): bool
    {
        if ($actor === null || ! app(Authorizer::class)->enabled()) {
            return true;
        }

        $permissions = app(Permissions::class);

        return ! $role->super
            && array_diff($role->permissions()->pluck('name')->all(), $permissions->for($actor, $organization)) === [];
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

    private function owned(Organization $organization, string $email): bool
    {
        return $organization->domains()->where('domain', strtolower(Str::after($email, '@')))->exists();
    }

    private function find(string $email): ?Model
    {
        $column = $this->users->column('email');

        return $column === null ? null : $this->users->query()
            ->whereLike($column, $email)
            ->get()
            ->first(fn (Model $user): bool => strcasecmp((string) $this->users->email($user), $email) === 0);
    }
}
