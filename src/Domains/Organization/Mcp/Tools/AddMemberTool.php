<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Organization\Mcp\Requests\AddMemberMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesOrganization;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Add an existing user to an organization.')]
final class AddMemberTool extends Tool
{
    use DescribesOrganization;

    public function handle(AddMemberMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'user' => $schema->string()->description('The user id to add.')->required(),
        ];
    }
}
