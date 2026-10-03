<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\Audit\Mcp\Requests\RecordAuditEventMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Record one of the application\'s own events in the audit log, e.g. invoice.paid. Always recorded with source app and you as the actor.')]
final class RecordAuditEventTool extends Tool
{
    public function handle(RecordAuditEventMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description('Dot-separated lower-case name, e.g. invoice.paid.')->required(),
            'subject_type' => $schema->string()->description('What it happened to, e.g. invoice.'),
            'subject_id' => $schema->string()->description('Its id.'),
            'subject_label' => $schema->string()->description('A human-readable name for it.'),
            'organization' => $schema->string()->description('The organization slug it belongs to, so its admins can see it.'),
            'changes' => $schema->object()->description('Field changes as {"field": [old, new]}.'),
            'context' => $schema->object()->description('Extra details to keep with the entry.'),
        ];
    }
}
