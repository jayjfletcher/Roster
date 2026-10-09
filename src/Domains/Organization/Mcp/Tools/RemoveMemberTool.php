<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Foundation\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\RemoveMemberMcpRequest;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('Remove a member from an organization, with every team seat they held. The owner cannot be removed.')]
final class RemoveMemberTool extends Tool
{
    use DescribesOrganization;

    public function handle(RemoveMemberMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'user' => $schema->string()->description('The user id, as returned by list-users-tool.')->required(),
        ];
    }
}
