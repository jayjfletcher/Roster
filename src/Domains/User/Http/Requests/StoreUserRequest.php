<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\User\Actions\CreateUserAction;
use JayI\Roster\Domains\User\Resources\UserResource;
use JayI\Roster\Http\Request;

final class StoreUserRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.create';
    }

    public function rules(): array
    {
        return CreateUserAction::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(CreateUserAction::class)->execute($this->validated());

        return (new UserResource($user))->response()->setStatusCode(201);
    }
}
