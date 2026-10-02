<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Models\Organization;

/**
 * A deleted organization is about to be restored.
 */
final class OrganizationRestoringActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
    ) {}
}
