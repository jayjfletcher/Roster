<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Foundation\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\PurgeOrganizationMcpRequest;

#[Description('Permanently delete a deleted organization with its teams, memberships, invitations, roles, SSO connections and SCIM tokens. Cannot be undone. It must be deleted (delete-organization-tool) first.')]
final class PurgeOrganizationTool extends Tool
{
    public function handle(PurgeOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'organization' => $schema->string()->description('The organization slug.')->required(),
        ];
    }
}
