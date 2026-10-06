<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\Permission\Mcp\Requests\UpdatePermissionMcpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Change a permission\'s description. Names cannot change.')]
final class UpdatePermissionTool extends Tool
{
    public function handle(UpdatePermissionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The permission name.')->required(),
            'description' => $schema->string()->description('New description, or null to clear.')->required(),
        ];
    }
}
