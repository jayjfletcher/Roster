<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\ListRoleAssignmentsMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesUser;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List the roles a user holds, with the organization or team each applies in.')]
final class ListRoleAssignmentsTool extends Tool
{
    use DescribesUser;

    public function handle(ListRoleAssignmentsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'organization' => $schema->string()->description('An organization slug.'),
            'per_page' => $schema->integer()->description('Results per page, 1-100.')->min(1),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
