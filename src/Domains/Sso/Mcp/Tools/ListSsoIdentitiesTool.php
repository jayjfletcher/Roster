<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Foundation\Mcp\Tool;
use RefactorCircus\Roster\Domains\Sso\Mcp\Requests\ListSsoIdentitiesMcpRequest;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('List the identity-provider accounts linked to a user.')]
final class ListSsoIdentitiesTool extends Tool
{
    use DescribesUser;

    public function handle(ListSsoIdentitiesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'per_page' => $schema->integer()->description('Results per page, 1-100.')->min(1)->max(100),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
