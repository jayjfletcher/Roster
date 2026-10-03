<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Organization\Mcp\Requests\DeleteOrganizationMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesOrganization;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Delete an organization with its teams, memberships and invitations. Personal organizations cannot be deleted.')]
final class DeleteOrganizationTool extends Tool
{
    use DescribesOrganization;

    public function handle(DeleteOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema);
    }
}
