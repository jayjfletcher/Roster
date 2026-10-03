<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Transfer\Mcp\Requests\StartExportMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Export members (of an organization), users, the audit log or organizations (in the import_organizations columns, so an edited file imports back) as CSV in the background. Check progress with show-transfer-tool, which returns a short-lived download_url when it is ready.')]
final class StartExportTool extends Tool
{
    public function handle(StartExportMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('export_members, export_users, export_audit or export_organizations.')->required(),
            'organization' => $schema->string()->description('An organization slug; required for export_members, optional for export_audit.'),
            'filters' => $schema->object([
                'source' => $schema->string()->description('roster or app.'),
                'action' => $schema->string()->description('An audit action, or a prefix ending in a dot.'),
                'since' => $schema->string()->description('Only entries at or after this date.'),
                'until' => $schema->string()->description('Only entries at or before this date.'),
                'external_source' => $schema->string()->description('export_organizations only: just records from this external system, e.g. erp.'),
            ])->description('Audit export filters (source, action, since, until), or external_source for organizations.'),
        ];
    }
}
