<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Team\Mcp\Requests\ListTeamsMcpRequest;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('List an organization\'s teams by name, with member counts. Paginated.')]
final class ListTeamsTool extends Tool
{
    use DescribesOrganization;

    public function handle(ListTeamsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'per_page' => $schema->integer()->description('Results per page, 1-100. Defaults to 15.')->min(1)->max(100),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
