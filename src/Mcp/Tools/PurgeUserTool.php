<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\PurgeUserMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Permanently delete a deleted user: their personal organization, memberships, role assignments and profile go too. Cannot be undone. A user must be deleted (delete-user-tool) first.')]
final class PurgeUserTool extends Tool
{
    public function handle(PurgeUserMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()->description('The user id.')->required(),
        ];
    }
}
