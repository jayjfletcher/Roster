<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\Team\Mcp\Requests\ShowTeamMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesTeam;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Show a team with its members.')]
final class ShowTeamTool extends Tool
{
    use DescribesTeam;

    public function handle(ShowTeamMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->teamSchema($schema);
    }
}
