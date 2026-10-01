<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Events\Action\TeamCreatedActionEvent;
use JayI\Roster\Events\Action\TeamCreatingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use JayI\Roster\Support\Slugs;

final class CreateTeamAction
{
    /**
     * Pass the organization so the slug is checked within it.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?Organization $organization = null): array
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
    public function execute(Organization $organization, array $data): Team
    {
        TeamCreatingActionEvent::dispatch($organization, $data);

        $team = DB::transaction(function () use ($organization, $data): Team {
            $name = (string) $data['name'];
            $slug = is_string($data['slug'] ?? null) && $data['slug'] !== ''
                ? $data['slug']
                : Slugs::unique($name, Team::query()->where('organization_id', $organization->getKey()));

            return Team::query()->create([
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
