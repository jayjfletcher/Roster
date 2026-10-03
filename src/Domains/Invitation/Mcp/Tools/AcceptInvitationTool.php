<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Invitation\Mcp\Requests\AcceptInvitationMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Accept an invitation as the authenticated user, whose email must match the invitation.')]
final class AcceptInvitationTool extends Tool
{
    public function handle(AcceptInvitationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'token' => $schema->string()->description('The token from the invitation link.')->required(),
        ];
    }
}
