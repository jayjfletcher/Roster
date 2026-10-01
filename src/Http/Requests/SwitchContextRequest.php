<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\SwitchContextAction;
use JayI\Roster\Http\Resources\UserResource;

final class SwitchContextRequest extends UserRequest
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
        return SwitchContextAction::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(SwitchContextAction::class)->execute($this->targetUser(), $this->validated());

        return (new UserResource($user))->response();
    }
}
