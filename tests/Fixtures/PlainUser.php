<?php

declare(strict_types=1);

namespace JayI\Roster\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A host user model that knows nothing about Roster.
 */
final class PlainUser extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];
}
