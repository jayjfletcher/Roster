<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\Scim\Mcp\Requests\RevokeScimTokenMcpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

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
