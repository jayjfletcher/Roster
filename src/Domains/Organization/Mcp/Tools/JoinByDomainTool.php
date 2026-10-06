<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\Organization\Mcp\Requests\JoinByDomainMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesUser;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Join a user to every auto-join organization that owns their email\'s domain. Does nothing unless their email is verified. Returns the organizations newly joined.')]
final class JoinByDomainTool extends Tool
{
    use DescribesUser;

    public function handle(JoinByDomainMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema);
    }
}
