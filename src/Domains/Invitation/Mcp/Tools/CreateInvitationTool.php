<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\Invitation\Mcp\Requests\CreateInvitationMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesOrganization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Invite an email address into an organization, optionally onto some of its teams, and email them the link. Works for people who have not registered yet.')]
final class CreateInvitationTool extends Tool
{
    use DescribesOrganization;

    public function handle(CreateInvitationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'email' => $schema->string()->description('The address to invite.')->required(),
            'teams' => $schema->array()->items($schema->string())->description('Team slugs in this organization to join on acceptance.'),
        ];
    }
}
