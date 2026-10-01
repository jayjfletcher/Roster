<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\CreateUserAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\UserResource;

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
