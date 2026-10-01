<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\ListImpersonationsMcpRequest;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List impersonations, newest first: who acted as whom, why, and when it ended.')]
final class ListImpersonationsTool extends Tool
{
    public function handle(ListImpersonationsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'active' => $schema->boolean()->description('Only impersonations in progress.'),
            'user' => $schema->string()->description('Only those acting as this user id.'),
            'impersonator' => $schema->string()->description('Only those by this user id.'),
            'organization' => $schema->string()->description('An organization slug.'),
            'per_page' => $schema->integer()->description('Results per page, 1-100.')->min(1)->max(100),
            'page' => $schema->integer()->description('Page number, from 1.')->min(1),
        ];
    }
}
