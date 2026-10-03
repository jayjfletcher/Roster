<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * An organization's invitations are about to be listed.
 */
final class InvitationsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        /** @var array<string, mixed> */
        public array $filters,
    ) {}
}
