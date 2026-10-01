<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\ListUsersAction;
use JayI\Roster\Http\Resources\UserResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

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
