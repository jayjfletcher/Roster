<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\ListUsersAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Mcp\Request;

final class ListUsersMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.view';
    }

    protected function rules(): array
    {
        return ListUsersAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $users = app(ListUsersAction::class)->execute($validated);

        return $this->structuredCollection(UserResource::collection($users->items())->resolve(), [
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}
