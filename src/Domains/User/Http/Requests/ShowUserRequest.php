<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\User\Actions\ShowUserAction;
use JayI\Roster\Domains\User\Resources\UserResource;

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
