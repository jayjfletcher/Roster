<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Services\Planners;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Organization\Models\MembershipModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Actions\AddTeamMemberAction;
use JayI\Roster\Domains\Team\Actions\CreateTeamAction;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Support\Users;
use RuntimeException;

/**
 * Teams in an organization: `name, slug, members` (emails of existing
 * members). Existing teams gain the listed members; nobody is removed.
 */
final class TeamsPlanner extends Planner
{
    public function __construct(private readonly Users $users) {}

    public function plan(array $values, TransferModel $transfer, ?Model $actor): array
    {
        $organization = $transfer->organization ?? throw new RuntimeException('A teams import needs an organization.');
        $name = $values['name'] ?? '';

        if ($name === '') {
            return $this->outcome(self::ERROR, __('roster::roster.import_team_name_required'));
        }

        [$members, $unknown] = $this->members($transfer, $values['members'] ?? '');

        if ($unknown !== []) {
            return $this->outcome(self::ERROR, __('roster::roster.import_not_members', ['emails' => implode(', ', $unknown)]));
        }

        $team = $this->team($organization, $values);

        if ($team === null) {
            return $this->outcome(self::CREATE, __('roster::roster.import_new_team'));
        }

        $seated = $team->seats()->pluck('membership_id')->all();
        $new = array_filter($members, fn (MembershipModel $membership): bool => ! in_array($membership->getKey(), $seated, true));

        return $new === []
            ? $this->outcome(self::SKIP, __('roster::roster.import_team_unchanged'))
            : $this->outcome(self::UPDATE, __('roster::roster.import_adds_members'));
    }

    public function apply(array $values, TransferModel $transfer, ?Model $actor): array
    {
        $plan = $this->plan($values, $transfer, $actor);

        if (in_array($plan['action'], [self::ERROR, self::SKIP], true)) {
            return $plan;
        }

        /** @var OrganizationModel $organization */
        $organization = $transfer->organization;
        $team = $this->team($organization, $values) ?? app(CreateTeamAction::class)->execute($organization, array_filter([
            'name' => $values['name'],
            'slug' => ($values['slug'] ?? '') !== '' ? $values['slug'] : null,
        ]));

        [$members] = $this->members($transfer, $values['members'] ?? '');

        foreach ($members as $membership) {
            $user = $membership->user;

            if ($user instanceof Model && ! $team->hasMember($user)) {
                app(AddTeamMemberAction::class)->execute($team, ['user' => $user]);
            }
        }

        return $plan;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function team(OrganizationModel $organization, array $values): ?TeamModel
    {
        $slug = $values['slug'] ?? '';

        return $organization->teams()
            ->where(fn (Builder $query): Builder => $slug !== '' ? $query->where('slug', $slug) : $query->where('name', $values['name'] ?? ''))
            ->first();
    }

    /**
     * @return array{0: array<int, MembershipModel>, 1: array<int, string>}
     */
    /**
     * The listed emails' memberships, from the organization's members loaded
     * once per transfer (a teams import never changes who is a member).
     *
     * @return array{0: array<int, MembershipModel>, 1: array<int, string>}
     */
    private function members(TransferModel $transfer, string $value): array
    {
        $byEmail = $this->remember($transfer, 'members', fn (): array => MembershipModel::query()
            ->with('user')
            ->where('organization_id', $transfer->organization_id)
            ->get()
            ->filter(fn (MembershipModel $membership): bool => $membership->user instanceof Model)
            ->keyBy(fn (MembershipModel $membership): string => strtolower((string) $this->users->email($membership->user)))
            ->all());

        $found = [];
        $unknown = [];

        foreach ($this->list($value) as $email) {
            $membership = $byEmail[strtolower($email)] ?? null;

            $membership === null ? $unknown[] = $email : $found[] = $membership;
        }

        return [$found, $unknown];
    }
}
