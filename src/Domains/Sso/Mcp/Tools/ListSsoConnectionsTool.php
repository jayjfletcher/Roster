<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\Sso\Mcp\Requests\ListSsoConnectionsMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesOrganization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List an organization\'s single sign-on connections. Secrets are never returned.')]
final class ListSsoConnectionsTool extends Tool
{
    use DescribesOrganization;

    public function handle(ListSsoConnectionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'per_page' => $schema->integer()->description('Results per page, 1-100.')->min(1)->max(100),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
