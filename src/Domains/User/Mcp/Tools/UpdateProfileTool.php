<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Domains\User\Mcp\Requests\UpdateProfileMcpRequest;
use JayI\Roster\Mcp\Concerns\DescribesProfilePayload;
use JayI\Roster\Mcp\Concerns\DescribesUser;
use JayI\Roster\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Update a user\'s profile. Only the fields given change; the profile is created if missing.')]
final class UpdateProfileTool extends Tool
{
    use DescribesProfilePayload;
    use DescribesUser;

    public function handle(UpdateProfileMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + $this->profileSchema($schema);
    }
}
