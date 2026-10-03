<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * An organization has been deleted permanently.
 */
final class OrganizationPurgedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
    ) {}
}
