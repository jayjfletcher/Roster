<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;

/**
 * An app event has been recorded in the audit log.
 */
final class AuditEventRecordedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AuditEntryModel $entry,
    ) {}
}
