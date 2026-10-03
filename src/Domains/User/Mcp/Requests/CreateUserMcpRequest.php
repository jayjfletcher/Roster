<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Requests;

use JayI\Roster\Domains\User\Actions\CreateUserAction;
use Laravel\Mcp\ResponseFactory;

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
