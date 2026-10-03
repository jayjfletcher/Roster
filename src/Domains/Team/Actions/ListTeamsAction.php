<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Events\TeamsListedActionEvent;
use JayI\Roster\Domains\Team\Events\TeamsListingActionEvent;
use JayI\Roster\Domains\Team\Models\TeamModel;

final class ListTeamsAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TeamModel>
     */
    public function execute(OrganizationModel $organization, array $filters = []): LengthAwarePaginator
    {
        TeamsListingActionEvent::dispatch($organization, $filters);

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 15;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $teams = $organization->teams()
            ->with('organization')
            ->withCount('seats')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        TeamsListedActionEvent::dispatch($organization, $filters);

        return $teams;
    }
}
