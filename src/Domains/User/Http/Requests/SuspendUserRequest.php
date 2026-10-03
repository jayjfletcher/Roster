<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\User\Actions\SuspendUserAction;
use JayI\Roster\Domains\User\Resources\UserResource;

final class SuspendUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    public function rules(): array
    {
        return SuspendUserAction::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(SuspendUserAction::class)->execute($this->targetUser(), $this->validated(), $this->actor());

        return (new UserResource($user))->response();
    }
}
