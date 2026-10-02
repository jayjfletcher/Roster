<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Models\Organization;

/**
 * An organization is about to be deleted permanently.
 */
final class OrganizationPurgingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
    ) {}
}
