<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * SCIM tokens have been listed.
 */
final class ScimTokensListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        /** @var array<string, mixed> */
        public array $filters,
    ) {}
}
