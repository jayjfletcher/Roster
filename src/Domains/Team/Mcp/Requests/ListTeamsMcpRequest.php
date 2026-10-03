<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Mcp\Requests;

use JayI\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Actions\ListTeamsAction;
use JayI\Roster\Domains\Team\Resources\TeamResource;
use Laravel\Mcp\ResponseFactory;

final class ListTeamsMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): OrganizationModel
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
