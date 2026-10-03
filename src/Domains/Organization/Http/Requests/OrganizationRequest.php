<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Http\Request;

abstract class OrganizationRequest extends Request
{
    private ?OrganizationModel $resolvedOrganization = null;

    /**
     * The organization named in the route, by slug.
     */
    protected function organization(): OrganizationModel
    {
        return $this->resolvedOrganization ??= OrganizationModel::query()
            ->where('slug', $this->route('organization'))
            ->firstOrFail();
    }
}
