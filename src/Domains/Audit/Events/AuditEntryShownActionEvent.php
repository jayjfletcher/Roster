<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;

/**
 * An audit entry has been shown.
 */
final class AuditEntryShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AuditEntryModel $entry,
    ) {}
}
