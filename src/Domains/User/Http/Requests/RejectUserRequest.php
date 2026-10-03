<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\User\Actions\RejectUserAction;
use JayI\Roster\Domains\User\Resources\UserResource;

final class RejectUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.approve';
    }

    public function rules(): array
    {
        return RejectUserAction::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(RejectUserAction::class)->execute($this->targetUser(), $this->validated(), $this->actor());

        return (new UserResource($user))->response();
    }
}
