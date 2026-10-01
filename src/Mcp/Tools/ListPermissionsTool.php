<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\ListPermissionsMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List every permission name roles can grant, alphabetically. Paginated.')]
final class ListPermissionsTool extends Tool
{
    public function handle(ListPermissionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Matches part of the name.'),
            'per_page' => $schema->integer()->description('Results per page, 1-100.')->min(1),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
