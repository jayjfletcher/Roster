<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use JayI\Roster\Models\Team;

abstract class TeamRequest extends OrganizationRequest
{
    private ?Team $resolvedTeam = null;

    /**
     * The team named in the route, by slug within the route's organization.
     */
    protected function team(): Team
    {
        return $this->resolvedTeam ??= Team::query()
            ->where('organization_id', $this->organization()->getKey())
            ->where('slug', $this->route('team'))
            ->firstOrFail();
    }
}
