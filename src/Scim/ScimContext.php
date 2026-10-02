<?php

declare(strict_types=1);

namespace JayI\Roster\Scim;

use JayI\Roster\Models\Organization;
use JayI\Roster\Models\ScimGroup;
use JayI\Roster\Models\ScimToken;
use JayI\Roster\Models\ScimUser;

/**
 * The SCIM token and organization the current request acts for. Bound per
 * request.
 */
final class ScimContext
{
    public ?ScimToken $token = null;

    /**
     * Groups per SCIM user id, resolved for a whole page at once.
     *
     * @var array<string, array<int, ScimGroup>>
     */
    public array $groups = [];

    /**
     * Members per SCIM group id, resolved for a whole page at once.
     *
     * @var array<string, array<int, ScimUser>>
     */
    public array $members = [];

    public function organization(): Organization
    {
        $organization = $this->token?->organization;

        if (! $organization instanceof Organization) {
            throw new ScimException(401, 'Authentication required.');
        }

        return $organization;
    }

    public function active(): bool
    {
        return $this->token !== null;
    }
}
