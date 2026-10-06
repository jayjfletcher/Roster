<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\User\Mcp\Requests\DeleteUserMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesUser;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Delete a user. Models using soft deletes keep their profile; otherwise the profile is deleted too. You cannot delete yourself.')]
final class DeleteUserTool extends Tool
{
    use DescribesUser;

    public function handle(DeleteUserMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema);
    }
}
