<?php

declare(strict_types=1);

namespace JayI\Roster\Support;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Models\AuditEntry;

/**
 * Who an audit entry is "about", for letting people read their own entries.
 */
final class AuditAccess
{
    /**
     * The actor, when the entry is about them or was made by them.
     */
    public static function selfFor(AuditEntry $entry, ?Model $actor): ?Model
    {
        if ($actor === null) {
            return null;
        }

        $made = $entry->actor_id !== null && (string) $entry->actor_id === (string) $actor->getKey();
        $about = $entry->subject_type === $actor->getMorphClass() && $entry->subject_id === (string) $actor->getKey();

        return $made || $about ? $actor : null;
    }
}
