<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Mcp\Request;
use RefactorCircus\Roster\Support\Users;

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
