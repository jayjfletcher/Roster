<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;

/**
 * An audit entry is about to be shown.
 */
final class AuditEntryShowingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AuditEntryModel $entry,
    ) {}
}
