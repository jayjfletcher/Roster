<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Data;

use JayI\Roster\Domains\Organization\Models\OrganizationLinkModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * What syncing one external record did to its organization.
 */
final readonly class OrganizationSyncResult
{
    public const string CREATED = 'created';

    public const string UPDATED = 'updated';

    public const string UNCHANGED = 'unchanged';

    public function __construct(
        public OrganizationModel $organization,
        public OrganizationLinkModel $link,
        public string $outcome,
    ) {}
}
