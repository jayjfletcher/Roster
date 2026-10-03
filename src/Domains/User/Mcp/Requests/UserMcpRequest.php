<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\User\Resources\UserResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Support\Users;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

abstract class UserMcpRequest extends Request
{
    private ?Model $target = null;

    /**
     * The user named by the `user` argument, found by the user model's route key.
     */
    protected function targetUser(): Model
    {
        return $this->target ??= app(Users::class)->findOrFail($this->get('user'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function userRules(): array
    {
        return ['user' => ['required']];
    }

    protected function respond(Model $user): ResponseFactory
    {
        return Response::structured(['data' => (new UserResource($user))->resolve()]);
    }
}
