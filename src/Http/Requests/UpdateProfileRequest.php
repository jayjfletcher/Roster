<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\UpdateProfileAction;
use JayI\Roster\Http\Resources\UserResource;

final class UpdateProfileRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return UpdateProfileAction::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(UpdateProfileAction::class)->execute($this->targetUser(), $this->validated());

        return (new UserResource($user))->response();
    }
}
