<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Foundation\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * An organization has been synced from an external system.
 */
final class OrganizationSyncedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        public string $source,
        public string $external_id,
        public ?string $account_number,
        public string $outcome,
    ) {}
}
