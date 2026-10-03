<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * A deleted organization has been restored.
 */
final class OrganizationRestoredActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
    ) {}
}
