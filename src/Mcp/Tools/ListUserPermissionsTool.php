<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\ListUserPermissionsMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesUser;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('The permissions a user effectively holds: globally, or in an organization or one of its teams. Includes whether they are a super-admin.')]
final class ListUserPermissionsTool extends Tool
{
    use DescribesUser;

    public function handle(ListUserPermissionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'organization' => $schema->string()->description('An organization slug.'),
            'team' => $schema->string()->description('A team slug in the organization.'),
        ];
    }
}
