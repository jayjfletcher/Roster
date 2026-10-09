<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Actions;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Events\TeamCreatedActionEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamCreatingActionEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Slugs;

final class CreateTeamAction
{
    /**
     * Pass the organization so the slug is checked within it.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?OrganizationModel $organization = null): array
    {
        $slug = Rule::unique('roster_teams', 'slug');

        if ($organization !== null) {
            $slug->where(fn (Builder $query): Builder => $query->where('organization_id', $organization->getKey()));
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255', $slug],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(OrganizationModel $organization, array $data): TeamModel
    {
        TeamCreatingActionEvent::dispatch($organization, $data);

        $team = DB::transaction(function () use ($organization, $data): TeamModel {
            $name = (string) $data['name'];
            $slug = is_string($data['slug'] ?? null) && $data['slug'] !== ''
                ? $data['slug']
                : Slugs::unique($name, TeamModel::query()->where('organization_id', $organization->getKey()));

            return TeamModel::query()->create([
                'organization_id' => $organization->getKey(),
                'name' => $name,
                'slug' => $slug,
            ]);
        });

        $team->load(['organization', 'memberships.user'])->loadCount('seats');

        TeamCreatedActionEvent::dispatch($team);

        return $team;
    }
}
