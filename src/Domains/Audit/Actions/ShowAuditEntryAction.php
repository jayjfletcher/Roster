<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Actions;

use JayI\Roster\Domains\Audit\Events\AuditEntryShowingActionEvent;
use JayI\Roster\Domains\Audit\Events\AuditEntryShownActionEvent;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;

final class ShowAuditEntryAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(AuditEntryModel $entry): AuditEntryModel
    {
        AuditEntryShowingActionEvent::dispatch($entry);

        $entry->loadMissing(['actor', 'organization']);

        AuditEntryShownActionEvent::dispatch($entry);

        return $entry;
    }
}
