<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use JayI\Roster\Events\Action\AuditEntryShowingActionEvent;
use JayI\Roster\Events\Action\AuditEntryShownActionEvent;
use JayI\Roster\Models\AuditEntry;

final class ShowAuditEntryAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(AuditEntry $entry): AuditEntry
    {
        AuditEntryShowingActionEvent::dispatch($entry);

        $entry->loadMissing(['actor', 'organization']);

        AuditEntryShownActionEvent::dispatch($entry);

        return $entry;
    }
}
