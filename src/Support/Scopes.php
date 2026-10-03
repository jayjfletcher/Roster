<?php

declare(strict_types=1);

namespace JayI\Roster\Support;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;

/**
 * The organization or team named by request input, for authorizing before
 * validation runs. Unknown slugs resolve to null and fail validation later.
 */
final class Scopes
{
    public static function fromInput(mixed $organization, mixed $team = null): OrganizationModel|TeamModel|null
    {
        if (! is_string($organization) || $organization === '') {
            return null;
        }

        $model = OrganizationModel::query()->where('slug', $organization)->first();

        if ($model === null || ! is_string($team) || $team === '') {
            return $model;
        }

        return TeamModel::query()->where('organization_id', $model->getKey())->where('slug', $team)->first() ?? $model;
    }
}
