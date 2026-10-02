<?php

declare(strict_types=1);

namespace JayI\Roster\Support;

use JayI\Roster\Models\Organization;
use JayI\Roster\Models\OrganizationLink;

/**
 * What syncing one external record did to its organization.
 */
final readonly class OrganizationSyncResult
{
    public const string CREATED = 'created';

    public const string UPDATED = 'updated';

    public const string UNCHANGED = 'unchanged';

    public function __construct(
        public Organization $organization,
        public OrganizationLink $link,
        public string $outcome,
    ) {}
}
