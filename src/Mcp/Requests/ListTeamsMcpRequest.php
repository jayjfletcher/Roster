<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ListTeamsAction;
use JayI\Roster\Http\Resources\TeamResource;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\ResponseFactory;

final class ListTeamsMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    protected function rules(): array
    {
        return ListTeamsAction::rules() + $this->organizationRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $teams = app(ListTeamsAction::class)->execute($this->organization(), $this->without($validated, 'organization'));

        return $this->structuredCollection(TeamResource::collection($teams->items())->resolve(), [
            'meta' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
            ],
        ]);
    }
}
