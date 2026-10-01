<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Planners;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Support\Users;
use RuntimeException;

/**
 * Teams in an organization: `name, slug, members` (emails of existing
 * members). Existing teams gain the listed members; nobody is removed.
 */
final class TeamsPlanner extends Planner
{
    public function __construct(private readonly Users $users) {}

    public function plan(array $values, Transfer $transfer, ?Model $actor): array
    {
        $organization = $transfer->organization ?? throw new RuntimeException('A teams import needs an organization.');
        $name = $values['name'] ?? '';

        if ($name === '') {
            return $this->outcome(self::ERROR, __('roster::roster.import_team_name_required'));
        }

        [$members, $unknown] = $this->members($organization, $values['members'] ?? '');

        if ($unknown !== []) {
            return $this->outcome(self::ERROR, __('roster::roster.import_not_members', ['emails' => implode(', ', $unknown)]));
        }

        $team = $this->team($organization, $values);

        if ($team === null) {
            return $this->outcome(self::CREATE, __('roster::roster.import_new_team'));
        }

        $new = array_filter($members, fn (Membership $membership): bool => ! $membership->teams()->whereKey($team->getKey())->exists());

        return $new === []
            ? $this->outcome(self::SKIP, __('roster::roster.import_team_unchanged'))
            : $this->outcome(self::UPDATE, __('roster::roster.import_adds_members'));
    }

    public function apply(array $values, Transfer $transfer, ?Model $actor): array
    {
        $plan = $this->plan($values, $transfer, $actor);

        if (in_array($plan['action'], [self::ERROR, self::SKIP], true)) {
            return $plan;
        }

        /** @var Organization $organization */
        $organization = $transfer->organization;
        $team = $this->team($organization, $values) ?? app(CreateTeamAction::class)->execute($organization, array_filter([
            'name' => $values['name'],
            'slug' => ($values['slug'] ?? '') !== '' ? $values['slug'] : null,
        ]));

        [$members] = $this->members($organization, $values['members'] ?? '');

        foreach ($members as $membership) {
            $user = $membership->user;

            if ($user instanceof Model && ! $team->hasMember($user)) {
                app(AddTeamMemberAction::class)->execute($team, ['user' => $user->getRouteKey()]);
            }
        }

        return $plan;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function team(Organization $organization, array $values): ?Team
    {
        $slug = $values['slug'] ?? '';

        return $organization->teams()
            ->where(fn (Builder $query): Builder => $slug !== '' ? $query->where('slug', $slug) : $query->where('name', $values['name'] ?? ''))
            ->first();
    }

    /**
     * @return array{0: array<int, Membership>, 1: array<int, string>}
     */
    private function members(Organization $organization, string $value): array
    {
        $found = [];
        $unknown = [];
        $column = $this->users->column('email');

        foreach ($this->list($value) as $email) {
            $user = $column === null ? null : $this->users->query()->whereLike($column, $email)->get()
                ->first(fn (Model $candidate): bool => strcasecmp((string) $this->users->email($candidate), $email) === 0);
            $membership = $user === null ? null : $organization->membershipFor($user);

            $membership === null ? $unknown[] = $email : $found[] = $membership;
        }

        return [$found, $unknown];
    }
}
