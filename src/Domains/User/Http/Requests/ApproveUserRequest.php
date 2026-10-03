<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\User\Actions\ApproveUserAction;
use JayI\Roster\Domains\User\Resources\UserResource;

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
