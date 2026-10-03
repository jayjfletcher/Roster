<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Transfer\Mcp\Requests\CancelTransferMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Cancel an import or export that has not finished. An import awaiting confirmation is dropped with nothing applied.')]
final class CancelTransferTool extends Tool
{
    public function handle(CancelTransferMcpRequest $request): Response|ResponseFactory
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
