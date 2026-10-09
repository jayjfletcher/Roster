<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * SCIM tokens are about to be listed.
 */
final class ScimTokensListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        /** @var array<string, mixed> */
        public array $filters,
    ) {}
}
