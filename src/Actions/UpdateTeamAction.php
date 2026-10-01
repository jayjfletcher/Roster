<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Events\Action\TeamUpdatedActionEvent;
use JayI\Roster\Events\Action\TeamUpdatingActionEvent;
use JayI\Roster\Models\Team;

final class UpdateTeamAction
{
    /**
     * Pass the team being updated so its own slug passes the unique check.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?Team $team = null): array
    {
        $slug = Rule::unique('roster_teams', 'slug');

        if ($team !== null) {
            $slug->ignore($team->getKey())
                ->where(fn (Builder $query): Builder => $query->where('organization_id', $team->organization_id));
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'alpha_dash', 'max:255', $slug],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Team $team, array $data): Team
    {
        TeamUpdatingActionEvent::dispatch($team, $data);

        DB::transaction(fn () => $team->update(array_intersect_key($data, array_flip(['name', 'slug']))));

        $team = $team->refresh()->load(['organization', 'memberships.user'])->loadCount('seats');

        TeamUpdatedActionEvent::dispatch($team);

        return $team;
    }
}
