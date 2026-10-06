<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\Roster\Domains\Transfer\Mcp\Requests\ShowImportTemplateMcpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Get the CSV template for an import type: its header row and commented example rows (rows starting with # are ignored on import). Fill it in and pass it to start-import-tool.')]
final class ShowImportTemplateTool extends Tool
{
    public function handle(ShowImportTemplateMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('import_members, import_users, import_teams or import_organizations.')->required(),
        ];
    }
}
