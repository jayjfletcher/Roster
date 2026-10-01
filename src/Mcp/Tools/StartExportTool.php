<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\StartExportMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Export members (of an organization), users or the audit log as CSV in the background. Check progress with show-transfer-tool, which returns a short-lived download_url when it is ready.')]
final class StartExportTool extends Tool
{
    public function handle(StartExportMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('export_members, export_users or export_audit.')->required(),
            'organization' => $schema->string()->description('An organization slug; required for export_members, optional for export_audit.'),
            'filters' => $schema->object([
                'source' => $schema->string()->description('roster or app.'),
                'action' => $schema->string()->description('An audit action, or a prefix ending in a dot.'),
                'since' => $schema->string()->description('Only entries at or after this date.'),
                'until' => $schema->string()->description('Only entries at or before this date.'),
            ])->description('Audit export filters.'),
        ];
    }
}
