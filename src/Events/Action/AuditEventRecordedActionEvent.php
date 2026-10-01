<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Models\AuditEntry;

/**
 * An app event has been recorded in the audit log.
 */
final class AuditEventRecordedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AuditEntry $entry,
    ) {}
}
