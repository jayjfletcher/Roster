<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\ListUsersAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Http\Request;

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
