<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Mcp\Requests;

use JayI\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Domains\Team\Resources\TeamResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

abstract class TeamMcpRequest extends OrganizationMcpRequest
{
    private ?TeamModel $resolvedTeam = null;

    /**
     * The team named by the `team` slug argument, within the organization.
     */
    protected function team(): TeamModel
    {
        return $this->resolvedTeam ??= TeamModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->where('slug', $this->get('team'))
            ->firstOrFail();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function teamRules(): array
    {
        return $this->organizationRules() + ['team' => ['required', 'string']];
    }

    protected function respondWithTeam(TeamModel $team): ResponseFactory
    {
        return Response::structured(['data' => (new TeamResource($team))->resolve()]);
    }
}
