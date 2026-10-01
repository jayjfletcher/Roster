<?php

declare(strict_types=1);

namespace JayI\Roster\Actions\Concerns;

use Illuminate\Validation\ValidationException;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;

/**
 * Turns organization and team slugs from input into models.
 */
trait ResolvesScopes
{
    private function organizationFrom(mixed $slug): ?Organization
    {
        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return Organization::query()->where('slug', $slug)->first()
            ?? throw ValidationException::withMessages(['organization' => __('roster::roster.unknown_organization')]);
    }

    private function teamFrom(?Organization $organization, mixed $slug): ?Team
    {
        if (! is_string($slug) || $slug === '') {
            return null;
        }

        if ($organization === null) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.team_needs_organization')]);
        }

        return Team::query()->where('organization_id', $organization->getKey())->where('slug', $slug)->first()
            ?? throw ValidationException::withMessages(['team' => __('roster::roster.unknown_team')]);
    }
}
