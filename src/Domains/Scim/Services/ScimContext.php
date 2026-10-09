<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Services;

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Exceptions\ScimException;
use RefactorCircus\Roster\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimUserModel;

/**
 * The SCIM token and organization the current request acts for. Bound per
 * request.
 */
final class ScimContext
{
    public ?ScimTokenModel $token = null;

    /**
     * Groups per SCIM user id, resolved for a whole page at once.
     *
     * @var array<string, array<int, ScimGroupModel>>
     */
    public array $groups = [];

    /**
     * Members per SCIM group id, resolved for a whole page at once.
     *
     * @var array<string, array<int, ScimUserModel>>
     */
    public array $members = [];

    public function organization(): OrganizationModel
    {
        $organization = $this->token?->organization;

        if (! $organization instanceof OrganizationModel) {
            throw new ScimException(401, 'Authentication required.');
        }

        return $organization;
    }

    public function active(): bool
    {
        return $this->token !== null;
    }
}
