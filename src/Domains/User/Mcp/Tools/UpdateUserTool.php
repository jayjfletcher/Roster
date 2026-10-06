<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\User\Mcp\Requests\UpdateUserMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesUser;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Update a user\'s account fields. Only the fields given change.')]
final class UpdateUserTool extends Tool
{
    use DescribesUser;

    public function handle(UpdateUserMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'name' => $schema->string()->description('Account name.'),
            'email' => $schema->string()->description('Unique email address.'),
            'password' => $schema->string()->description('New password, at least 8 characters.'),
        ];
    }
}
