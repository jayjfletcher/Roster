<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Requests\DeletePermissionMcpRequest;

#[Description('Delete an app permission. Built-in roster.* permissions cannot be deleted.')]
final class DeletePermissionTool extends Tool
{
    public function handle(DeletePermissionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The permission name.')->required(),
        ];
    }
}
