<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use JayI\Roster\Http\Request;
use JayI\Roster\Models\Organization;

abstract class OrganizationRequest extends Request
{
    private ?Organization $resolvedOrganization = null;

    /**
     * The organization named in the route, by slug.
     */
    protected function organization(): Organization
    {
        return $this->resolvedOrganization ??= Organization::query()
            ->where('slug', $this->route('organization'))
            ->firstOrFail();
    }
}
