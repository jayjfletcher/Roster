<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Http\Resources\TeamResource;
use JayI\Roster\Models\Team;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

abstract class TeamMcpRequest extends OrganizationMcpRequest
{
    private ?Team $resolvedTeam = null;

    /**
     * The team named by the `team` slug argument, within the organization.
     */
    protected function team(): Team
    {
        return $this->resolvedTeam ??= Team::query()
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

    protected function respondWithTeam(Team $team): ResponseFactory
    {
        return Response::structured(['data' => (new TeamResource($team))->resolve()]);
    }
}
