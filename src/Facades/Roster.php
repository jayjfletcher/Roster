<?php

declare(strict_types=1);

namespace JayI\Roster\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \JayI\Roster\Audit\PendingAuditEntry audit(string $action)
 * @method static \JayI\Roster\Models\Organization|null organization(\Illuminate\Database\Eloquent\Model $user)
 * @method static \JayI\Roster\Models\Team|null team(\Illuminate\Database\Eloquent\Model $user)
 *
 * @see \JayI\Roster\Roster
 */
class Roster extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JayI\Roster\Roster::class;
    }
}
