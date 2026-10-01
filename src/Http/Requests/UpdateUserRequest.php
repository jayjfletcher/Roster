<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\UpdateUserAction;
use JayI\Roster\Http\Resources\UserResource;

final class UpdateUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    public function rules(): array
    {
        return UpdateUserAction::rules($this->targetUser());
    }

    public function persist(): JsonResponse
    {
        $user = app(UpdateUserAction::class)->execute($this->targetUser(), $this->validated());

        return (new UserResource($user))->response();
    }
}
