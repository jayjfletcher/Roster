<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\ListOrganizationsMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List organizations by name. Filter by a search term or by a member user. Paginated.')]
final class ListOrganizationsTool extends Tool
{
    public function handle(ListOrganizationsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Matches name or slug.'),
            'user' => $schema->string()->description('Only organizations this user id belongs to.'),
            'per_page' => $schema->integer()->description('Results per page, 1-100. Defaults to 15.')->min(1)->max(100),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
