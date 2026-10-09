<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Scim\Mcp\Requests\RevokeScimTokenMcpRequest;

#[Description('Revoke a SCIM token so it stops working immediately.')]
final class RevokeScimTokenTool extends Tool
{
    public function handle(RevokeScimTokenMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'token' => $schema->string()->description('The token id (not its value).')->required(),
        ];
    }
}
