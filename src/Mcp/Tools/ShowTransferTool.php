<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\ShowTransferMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Show an import or export with its per-row report. A finished export includes a download_url valid for 15 minutes.')]
final class ShowTransferTool extends Tool
{
    public function handle(ShowTransferMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'transfer' => $schema->string()->description('The transfer id.')->required(),
        ];
    }
}
