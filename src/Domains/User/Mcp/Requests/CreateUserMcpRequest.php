<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;

final class CreateUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.create';
    }

    protected function rules(): array
    {
        return CreateUserAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->respond(app(CreateUserAction::class)->execute($validated));
    }
}
