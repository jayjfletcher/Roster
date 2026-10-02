<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\CreateOrganizationMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesOrganization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create an organization. The owner becomes its first member.')]
final class CreateOrganizationTool extends Tool
{
    use DescribesOrganization;

    public function handle(CreateOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Display name.')->required(),
            'slug' => $schema->string()->description('URL identifier (letters, numbers, dashes). Omit to generate one from the name.'),
            'owner' => $schema->string()->description('The owning user id. Optional: an organization may have no owner until ownership is transferred to a member.'),
        ] + $this->organizationSettingsSchema($schema);
    }
}
