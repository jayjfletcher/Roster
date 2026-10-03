<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Role\Mcp\Requests\ListRolesMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List roles. With organization, the roles usable there (shared ones plus its own). Filter by scope. Paginated.')]
final class ListRolesTool extends Tool
{
    public function handle(ListRolesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()->description('global, organization or team.'),
            'organization' => $schema->string()->description('An organization slug.'),
            'per_page' => $schema->integer()->description('Results per page, 1-100.')->min(1),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
