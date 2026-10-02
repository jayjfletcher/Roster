<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ApproveUserAction;
use JayI\Roster\Http\Resources\UserResource;

final class ApproveUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.approve';
    }

    public function rules(): array
    {
        return ApproveUserAction::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(ApproveUserAction::class)->execute($this->targetUser(), $this->validated(), $this->actor());

        return (new UserResource($user))->response();
    }
}
