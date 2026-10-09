<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\UnlinkOrganizationMcpRequest;

#[Description('Remove an organization\'s link to one external system.')]
final class UnlinkOrganizationTool extends Tool
{
    public function handle(UnlinkOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'organization' => $schema->string()->description('The organization slug.')->required(),
            'source' => $schema->string()->description('The external system to unlink.')->required(),
        ];
    }
}
