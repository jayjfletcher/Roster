<?php

declare(strict_types=1);

namespace JayI\Roster\Scim;

use JayI\Roster\Models\Organization;
use JayI\Roster\Models\ScimToken;

/**
 * The SCIM token and organization the current request acts for. Bound per
 * request.
 */
final class ScimContext
{
    public ?ScimToken $token = null;

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
