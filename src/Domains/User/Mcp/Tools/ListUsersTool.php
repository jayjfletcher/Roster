<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\User\Mcp\Requests\ListUsersMcpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List users, oldest first. Filter by status or search name, email and display name. Paginated.')]
final class ListUsersTool extends Tool
{
    public function handle(ListUsersMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Matches name, email or display name.'),
            'status' => $schema->string()->description('active, pending, suspended or deactivated.'),
            'trashed' => $schema->string()->description('only: deleted users only; with: deleted ones too. Needs a soft-deleting user model.'),
            'per_page' => $schema->integer()->description('Results per page, 1-100. Defaults to 15.')->min(1)->max(100),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
