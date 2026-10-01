<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\ShowAuditEntryMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Show one audit entry with its field changes and chain hash.')]
final class ShowAuditEntryTool extends Tool
{
    public function handle(ShowAuditEntryMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entry' => $schema->integer()->description('The audit entry id.')->required(),
        ];
    }
}
