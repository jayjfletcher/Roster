<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\DeleteTeamMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesTeam;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Delete a team. Its members stay in the organization.')]
final class DeleteTeamTool extends Tool
{
    use DescribesTeam;

    public function handle(DeleteTeamMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->teamSchema($schema);
    }
}
