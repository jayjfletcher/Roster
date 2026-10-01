<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\UpdateOrganizationMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesOrganization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Update an organization\'s name, slug, auto-join setting or domains. Only the fields given change.')]
final class UpdateOrganizationTool extends Tool
{
    use DescribesOrganization;

    public function handle(UpdateOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'name' => $schema->string()->description('Display name.'),
            'slug' => $schema->string()->description('New URL identifier.'),
        ] + $this->organizationSettingsSchema($schema);
    }
}
