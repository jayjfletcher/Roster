<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\Organization\Mcp\Requests\LinkOrganizationMcpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Record or change an organization\'s id and account number in one external system. An id already linked to another organization is refused.')]
final class LinkOrganizationTool extends Tool
{
    public function handle(LinkOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'organization' => $schema->string()->description('The organization slug.')->required(),
            'source' => $schema->string()->description('The external system, lower case, e.g. erp or crm.')->required(),
            'external_id' => $schema->string()->description('The organization\'s id in that system.')->required(),
            'account_number' => $schema->string()->description('Its account number in that system.'),
        ];
    }
}
