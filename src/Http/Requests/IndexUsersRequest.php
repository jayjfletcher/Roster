<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ListUsersAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\UserResource;

final class IndexUsersRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.view';
    }

    public function rules(): array
    {
        return ListUsersAction::rules();
    }

    public function persist(): JsonResponse
    {
        return UserResource::collection(app(ListUsersAction::class)->execute($this->validated()))->response();
    }
}
