<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Role\Mcp\Requests\DeleteRoleMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Delete a role and all its assignments. Built-in roles cannot be deleted.')]
final class DeleteRoleTool extends Tool
{
    public function handle(DeleteRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'role' => $schema->string()->description('The role id.')->required(),
        ];
    }
}
