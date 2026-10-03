<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\User\Mcp\Requests\SuspendUserMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesUser;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Suspend a user: a temporary block lifted by reactivate-user-tool. Suspended users are rejected by the roster.active middleware. You cannot suspend yourself.')]
final class SuspendUserTool extends Tool
{
    use DescribesUser;

    public function handle(SuspendUserMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'reason' => $schema->string()->description('Why, recorded on the profile.'),
        ];
    }
}
