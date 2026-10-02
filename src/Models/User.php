<?php

declare(strict_types=1);

namespace JayI\Roster\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use JayI\Roster\Concerns\HasRoster;

/**
 * A ready-made user model for apps without one of their own.
 *
 * Point `roster.users.model` and the auth provider at this class and publish
 * its migration with the `roster-users-migration` tag. Apps with their own
 * users keep them and use the HasRoster trait instead.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasRoster;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
