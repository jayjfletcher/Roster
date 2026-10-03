<?php

declare(strict_types=1);

namespace JayI\Roster\Support\Concerns;

use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;

/**
 * Turns organization and team slugs from input into models.
 */
trait ResolvesScopes
{
    /**
     * From a slug, or an organization the caller already has (no query).
     */
    private function organizationFrom(mixed $slug): ?OrganizationModel
    {
        if ($slug instanceof OrganizationModel) {
            return $slug;
        }

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return OrganizationModel::query()->where('slug', $slug)->first()
            ?? throw ValidationException::withMessages(['organization' => __('roster::roster.unknown_organization')]);
    }

    private function teamFrom(?OrganizationModel $organization, mixed $slug): ?TeamModel
    {
        if (! is_string($slug) || $slug === '') {
            return null;
        }

        if ($organization === null) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.team_needs_organization')]);
        }

        return TeamModel::query()->where('organization_id', $organization->getKey())->where('slug', $slug)->first()
            ?? throw ValidationException::withMessages(['team' => __('roster::roster.unknown_team')]);
    }
}
