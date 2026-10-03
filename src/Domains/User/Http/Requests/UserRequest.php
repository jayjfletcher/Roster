<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Http\Request;
use JayI\Roster\Support\Users;

abstract class UserRequest extends Request
{
    private ?Model $target = null;

    /**
     * The user named in the route, found by the user model's route key.
     */
    protected function targetUser(): Model
    {
        return $this->target ??= app(Users::class)->findOrFail($this->route('user'));
    }
}
