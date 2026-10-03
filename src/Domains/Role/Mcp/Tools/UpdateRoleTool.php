<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Role\Mcp\Requests\UpdateRoleMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Rename a role or replace its permissions. A given permissions list replaces the old one; you can only add permissions you hold.')]
final class UpdateRoleTool extends Tool
{
    public function handle(UpdateRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'role' => $schema->string()->description('The role id.')->required(),
            'name' => $schema->string()->description('Display name.'),
            'description' => $schema->string()->description('What the role is for.'),
            'permissions' => $schema->array()->items($schema->string())->description('Permission names. Replaces the whole list.'),
        ];
    }
}
