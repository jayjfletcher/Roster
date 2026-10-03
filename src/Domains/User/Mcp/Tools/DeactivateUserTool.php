<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\User\Mcp\Requests\DeactivateUserMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesUser;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Deactivate a user: the account is closed but kept, and can be reactivated. You cannot deactivate yourself.')]
final class DeactivateUserTool extends Tool
{
    use DescribesUser;

    public function handle(DeactivateUserMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'reason' => $schema->string()->description('Why, recorded on the profile.'),
        ];
    }
}
