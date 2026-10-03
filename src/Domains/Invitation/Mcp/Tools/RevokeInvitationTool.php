<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Invitation\Mcp\Requests\RevokeInvitationMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesOrganization;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Revoke a pending invitation so its link stops working.')]
final class RevokeInvitationTool extends Tool
{
    use DescribesOrganization;

    public function handle(RevokeInvitationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'invitation' => $schema->string()->description('The invitation id.')->required(),
        ];
    }
}
