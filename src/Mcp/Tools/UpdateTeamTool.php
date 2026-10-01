<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\UpdateTeamMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesTeam;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Rename a team or change its slug. Only the fields given change.')]
final class UpdateTeamTool extends Tool
{
    use DescribesTeam;

    public function handle(UpdateTeamMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->teamSchema($schema) + [
            'name' => $schema->string()->description('Display name.'),
            'slug' => $schema->string()->description('New slug, unique within the organization.'),
        ];
    }
}
