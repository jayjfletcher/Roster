<?php

declare(strict_types=1);

namespace JayI\Roster\Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use JayI\Roster\Domains\User\Concerns\HasRoster;

/**
 * A host user model keyed by ULID with non-standard column names.
 */
final class UlidUser extends Authenticatable
{
    use HasRoster;
    use HasUlids;

    protected $table = 'ulid_users';

    protected $fillable = ['full_name', 'email_address', 'secret'];
}
