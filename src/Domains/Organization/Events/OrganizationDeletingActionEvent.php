<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * An organization is about to be deleted.
 */
final class OrganizationDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
    ) {}
}
