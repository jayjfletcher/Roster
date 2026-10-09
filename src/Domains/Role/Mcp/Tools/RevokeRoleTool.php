<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Role\Mcp\Requests\RevokeRoleMcpRequest;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('Take a role assignment away from a user.')]
final class RevokeRoleTool extends Tool
{
    use DescribesUser;

    public function handle(RevokeRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'assignment' => $schema->string()->description('The assignment id from list-role-assignments-tool.')->required(),
        ];
    }
}
