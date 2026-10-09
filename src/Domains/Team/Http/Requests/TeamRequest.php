<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Requests;

use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

abstract class TeamRequest extends OrganizationRequest
{
    private ?TeamModel $resolvedTeam = null;

    /**
     * The team named in the route, by slug within the route's organization.
     */
    protected function team(): TeamModel
    {
        return $this->resolvedTeam ??= TeamModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->where('slug', $this->route('team'))
            ->firstOrFail();
    }
}
