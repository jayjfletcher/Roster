<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Foundation\Mcp\Tool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Requests\ListPermissionsMcpRequest;

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
