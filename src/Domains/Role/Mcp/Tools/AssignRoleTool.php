<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Role\Mcp\Requests\AssignRoleMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesUser;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Give a user a role: a global role with no organization; an organization role with organization (the user must be a member); a team role with organization and team (the user must sit on it). You can only assign roles whose permissions you hold.')]
final class AssignRoleTool extends Tool
{
    use DescribesUser;

    public function handle(AssignRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'role' => $schema->string()->description('The role id.')->required(),
            'organization' => $schema->string()->description('An organization slug.'),
            'team' => $schema->string()->description('A team slug in the organization, for team roles.'),
        ];
    }
}
