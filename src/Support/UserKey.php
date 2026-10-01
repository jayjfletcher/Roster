<?php

declare(strict_types=1);

namespace JayI\Roster\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;

/**
 * Columns referencing the host's users, typed by `roster.users.key_type`.
 *
 * Used by Roster's migrations; set the key type before migrating.
 */
final class UserKey
{
    public static function column(Blueprint $table, string $name): ColumnDefinition
    {
        return match (config('roster.users.key_type', 'int')) {
            'ulid' => $table->ulid($name),
            'uuid' => $table->uuid($name),
            default => $table->unsignedBigInteger($name),
        };
    }
}
