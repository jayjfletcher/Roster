<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\Audit\Mcp\Requests\ListAuditEntriesMcpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Read the audit log, newest first: who changed what, when, through which surface, with field changes. Filter by organization (its admins may read it), user (entries about or by them; you may always read your own), source (roster or app), action (exact, or a prefix ending in a dot such as user.), subject type and dates. Paginated.')]
final class ListAuditEntriesTool extends Tool
{
    public function handle(ListAuditEntriesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'organization' => $schema->string()->description('An organization slug.'),
            'user' => $schema->string()->description('A user id: entries about or by them.'),
            'source' => $schema->string()->description('roster (recorded by Roster) or app (recorded by the application).'),
            'action' => $schema->string()->description('An action such as user.suspended, or a prefix ending in a dot such as role.'),
            'subject_type' => $schema->string()->description('user, organization, team, role, permission, invitation or assignment.'),
            'since' => $schema->string()->description('Only entries at or after this date.'),
            'until' => $schema->string()->description('Only entries at or before this date.'),
            'per_page' => $schema->integer()->description('Results per page, 1-100. Defaults to 25.')->min(1)->max(100),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
