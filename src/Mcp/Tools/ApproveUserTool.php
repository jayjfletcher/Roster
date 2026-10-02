<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\ApproveUserMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesUser;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Approve an account awaiting approval (status pending): it becomes active. Only pending accounts can be approved.')]
final class ApproveUserTool extends Tool
{
    use DescribesUser;

    public function handle(ApproveUserMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema);
    }
}
