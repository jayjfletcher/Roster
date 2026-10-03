<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Actions;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Domains\Team\Events\TeamUpdatedActionEvent;
use JayI\Roster\Domains\Team\Events\TeamUpdatingActionEvent;
use JayI\Roster\Domains\Team\Models\TeamModel;

final class UpdateTeamAction
{
    /**
     * Pass the team being updated so its own slug passes the unique check.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?TeamModel $team = null): array
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
    public function execute(TeamModel $team, array $data): TeamModel
    {
        TeamUpdatingActionEvent::dispatch($team, $data);

        DB::transaction(fn () => $team->update(array_intersect_key($data, array_flip(['name', 'slug']))));

        $team = $team->refresh()->load(['organization', 'memberships.user'])->loadCount('seats');

        TeamUpdatedActionEvent::dispatch($team);

        return $team;
    }
}
