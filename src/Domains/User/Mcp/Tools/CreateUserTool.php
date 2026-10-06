<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\User\Mcp\Requests\CreateUserMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesProfilePayload;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a user and their profile. Without a password a random one is set; the user must reset it to sign in.')]
final class CreateUserTool extends Tool
{
    use DescribesProfilePayload;

    public function handle(CreateUserMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->profileSchema($schema) + [
            'name' => $schema->string()->description('Account name.')->required(),
            'email' => $schema->string()->description('Unique email address.')->required(),
            'password' => $schema->string()->description('At least 8 characters. Omit to set a random one.'),
            'status' => $schema->string()->description('active (default) or pending, to make the account wait for approval.'),
        ];
    }
}
