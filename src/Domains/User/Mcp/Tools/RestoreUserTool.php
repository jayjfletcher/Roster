<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\User\Mcp\Requests\RestoreUserMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Restore a deleted user, with the profile, memberships and roles they kept while deleted.')]
final class RestoreUserTool extends Tool
{
    public function handle(RestoreUserMcpRequest $request): Response|ResponseFactory
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
