<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\CreateTeamMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesOrganization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a team inside an organization.')]
final class CreateTeamTool extends Tool
{
    use DescribesOrganization;

    public function handle(CreateTeamMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'name' => $schema->string()->description('Display name.')->required(),
            'slug' => $schema->string()->description('URL identifier, unique within the organization. Omit to generate one.'),
        ];
    }
}
