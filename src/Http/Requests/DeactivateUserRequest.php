<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\DeactivateUserAction;
use JayI\Roster\Http\Resources\UserResource;

final class DeactivateUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    public function rules(): array
    {
        return DeactivateUserAction::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(DeactivateUserAction::class)->execute($this->targetUser(), $this->validated(), $this->actor());

        return (new UserResource($user))->response();
    }
}
