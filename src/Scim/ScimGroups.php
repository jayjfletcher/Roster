<?php

declare(strict_types=1);

namespace JayI\Roster\Scim;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Actions\DeleteTeamAction;
use JayI\Roster\Actions\RemoveTeamMemberAction;
use JayI\Roster\Actions\UpdateTeamAction;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\ScimGroup;
use JayI\Roster\Models\ScimUser;
use JayI\Roster\Models\Team;

/**
 * SCIM Groups for one organization: its teams, on top of Roster's Actions.
 */
final class ScimGroups
{
    public function __construct(private readonly ScimContext $context) {}

    /**
     * @return array{0: array<int, ScimGroup>, 1: int}
     */
    public function list(?string $filter, int $startIndex, int $count): array
    {
        $query = ScimGroup::query()->where('organization_id', $this->organization()->getKey())->with('team');

        if ($filter !== null && trim($filter) !== '') {
            foreach (FilterParser::parse($filter, ['displayname', 'externalid', 'id', 'members.value']) as $condition) {
                $value = (string) $condition['value'];

                match ($condition['attribute']) {
                    'displayname' => $query->whereHas('team', fn (Builder $team): Builder => ScimQuery::compare($team, 'name', $condition['operator'], $value)),
                    'externalid' => ScimQuery::compare($query, 'external_id', $condition['operator'], $value),
                    'id' => $query->where('id', $value),
                    default => $query->whereIn('team_id', $this->teamsOfMember($value)),
                };
            }
        }

        $total = (clone $query)->count();
        $page = $query->orderBy('created_at')->orderBy('id')->skip(max(0, $startIndex - 1))->take($count)->get()->all();

        return [$page, $total];
    }

    public function find(string $id): ScimGroup
    {
        return ScimGroup::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereKey($id)
            ->with('team')
            ->first() ?? throw ScimException::notFound('Group');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ScimGroup
    {
        $name = $this->displayName($data);
        $organization = $this->organization();
        $externalId = is_scalar($data['externalId'] ?? null) ? (string) $data['externalId'] : null;

        if ($organization->teams()->where('name', $name)->whereIn('id', ScimGroup::query()->select('team_id'))->exists()) {
            throw ScimException::uniqueness("A group named [{$name}] already exists.");
        }

        return DB::transaction(function () use ($data, $name, $organization, $externalId): ScimGroup {
            $team = app(CreateTeamAction::class)->execute($organization, ['name' => $name]);

            $group = ScimGroup::query()->create([
                'organization_id' => $organization->getKey(),
                'team_id' => $team->getKey(),
                'external_id' => $externalId,
            ]);

            $this->syncMembers($team, $this->memberIds($data));

            return $group->refresh()->load('team');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function replace(ScimGroup $group, array $data, bool $members = true): ScimGroup
    {
        $team = $group->team ?? throw ScimException::notFound('Group');
        $name = $this->displayName($data);

        return DB::transaction(function () use ($group, $team, $data, $name, $members): ScimGroup {
            if ($team->name !== $name) {
                app(UpdateTeamAction::class)->execute($team, ['name' => $name]);
            }

            if (array_key_exists('externalId', $data)) {
                $group->update(['external_id' => is_scalar($data['externalId']) ? (string) $data['externalId'] : null]);
            }

            if ($members) {
                $this->syncMembers($team, $this->memberIds($data));
            }

            $group->touch();

            return $group->refresh()->load('team');
        });
    }

    /**
     * @param  array<int, mixed>  $operations
     */
    public function patch(ScimGroup $group, array $operations): ScimGroup
    {
        $view = app(ScimMapper::class)->group($group);

        return $this->replace($group, PatchApplier::apply($view, $operations));
    }

    public function delete(ScimGroup $group): void
    {
        $team = $group->team;

        DB::transaction(function () use ($group, $team): void {
            $group->delete();

            if ($team instanceof Team) {
                app(DeleteTeamAction::class)->execute($team);
            }
        });
    }

    /**
     * Make the team's seats match exactly the given SCIM users.
     *
     * @param  array<int, string>  $scimUserIds
     */
    private function syncMembers(Team $team, array $scimUserIds): void
    {
        $wanted = ScimUser::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereIn('id', $scimUserIds)
            ->with('user')
            ->get();

        if ($wanted->count() !== count(array_unique($scimUserIds))) {
            throw ScimException::invalidValue('Every member must be a user provisioned in this organization.');
        }

        $current = $team->memberships()->pluck('user_id')->map(fn (mixed $id): string => (string) $id)->all();
        $wantedUsers = $wanted->filter(fn (ScimUser $scimUser): bool => $scimUser->active && $scimUser->user instanceof Model);

        foreach ($wantedUsers as $scimUser) {
            if (! in_array((string) $scimUser->user_id, $current, true)) {
                $this->attempt(fn () => app(AddTeamMemberAction::class)->execute($team, ['user' => $scimUser->user?->getRouteKey()]));
            }
        }

        $keep = $wantedUsers->map(fn (ScimUser $scimUser): string => (string) $scimUser->user_id)->all();

        foreach ($team->memberships()->with('user')->get() as $membership) {
            // Only seats SCIM can see are SCIM's to remove.
            $managed = ScimUser::query()->where('organization_id', $this->organization()->getKey())->where('user_id', $membership->user_id)->exists();

            if ($managed && ! in_array((string) $membership->user_id, $keep, true) && $membership->user instanceof Model) {
                $this->attempt(fn () => app(RemoveTeamMemberAction::class)->execute($team, $membership->user));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function memberIds(array $data): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $member): ?string => is_array($member) && is_scalar($member['value'] ?? null) ? (string) $member['value'] : null,
            (array) ($data['members'] ?? []),
        )));
    }

    /**
     * @return array<int, string>
     */
    private function teamsOfMember(string $scimUserId): array
    {
        $scimUser = ScimUser::query()->where('organization_id', $this->organization()->getKey())->whereKey($scimUserId)->first();
        $membership = $scimUser?->user instanceof Model ? $this->organization()->membershipFor($scimUser->user) : null;

        return $membership === null ? [] : $membership->teams()->pluck('roster_teams.id')->map(fn (mixed $id): string => (string) $id)->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function displayName(array $data): string
    {
        $name = $data['displayName'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            throw ScimException::invalidValue('displayName is required.');
        }

        return trim($name);
    }

    private function attempt(callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            throw ScimException::invalidValue((string) collect($exception->errors())->flatten()->first());
        }
    }

    private function organization(): Organization
    {
        return $this->context->organization();
    }
}
