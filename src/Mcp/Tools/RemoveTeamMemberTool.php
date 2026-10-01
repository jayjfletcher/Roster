<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\RemoveTeamMemberMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesTeam;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Remove a user from a team. They stay in the organization.')]
final class RemoveTeamMemberTool extends Tool
{
    use DescribesTeam;

    public function handle(RemoveTeamMemberMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->teamSchema($schema) + [
            'user' => $schema->string()->description('The user id, as returned by list-users-tool.')->required(),
        ];
    }
}
