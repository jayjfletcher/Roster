<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\SyncOrganizationsMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Sync a batch of external records (each with the fields of sync-organization-tool). Each record succeeds or fails on its own; the result lists created, updated, unchanged or error (with messages) per record, in order.')]
final class SyncOrganizationsTool extends Tool
{
    public function handle(SyncOrganizationsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'records' => $schema->array()->items($schema->object())->description('Records, each {source, external_id, name?, account_number?, slug?, domains?, auto_join?, owner?, organization?}.')->required(),
        ];
    }
}
