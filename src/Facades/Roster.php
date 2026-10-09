<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \RefactorCircus\Keen\Domains\Audit\Data\PendingAuditEntry audit(string $action) Deprecated: use `Keen::record()` from refactor-circus/keen.
 * @method static \RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel|null organization(\Illuminate\Database\Eloquent\Model $user)
 * @method static \RefactorCircus\Roster\Domains\Team\Models\TeamModel|null team(\Illuminate\Database\Eloquent\Model $user)
 *
 * @see \RefactorCircus\Roster\Roster
 */
class Roster extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RefactorCircus\Roster\Roster::class;
    }
}
