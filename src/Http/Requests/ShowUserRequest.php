<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ShowUserAction;
use JayI\Roster\Http\Resources\UserResource;

final class ShowUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return ShowUserAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new UserResource(app(ShowUserAction::class)->execute($this->targetUser())))->response();
    }
}
