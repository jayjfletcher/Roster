<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Requests\ListTransfersMcpRequest;

#[Description('List imports and exports, newest first: your own, an organization\'s, or (with roster.users.view) everyone\'s. Paginated.')]
final class ListTransfersTool extends Tool
{
    public function handle(ListTransfersMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'organization' => $schema->string()->description('An organization slug.'),
            'type' => $schema->string()->description('import_members, import_users, import_teams, export_members, export_users or export_organizations.'),
            'status' => $schema->string()->description('validating, awaiting_confirmation, running, completed, failed, cancelled or expired.'),
            'per_page' => $schema->integer()->description('Results per page, 1-100. Defaults to 25.')->min(1)->max(100),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
