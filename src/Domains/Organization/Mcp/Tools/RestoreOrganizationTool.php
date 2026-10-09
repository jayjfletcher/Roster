<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\RestoreOrganizationMcpRequest;

#[Description('Restore a deleted organization with everything it kept; its slug and domains stayed reserved.')]
final class RestoreOrganizationTool extends Tool
{
    public function handle(RestoreOrganizationMcpRequest $request): Response|ResponseFactory
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
