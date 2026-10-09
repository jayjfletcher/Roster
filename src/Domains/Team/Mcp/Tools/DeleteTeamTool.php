<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Foundation\Mcp\Tool;
use RefactorCircus\Roster\Domains\Team\Mcp\Requests\DeleteTeamMcpRequest;
use RefactorCircus\Roster\Mcp\Concerns\DescribesTeam;

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
