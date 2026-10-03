<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

trait DescribesUser
{
    /**
     * @return array<string, Type>
     */
    protected function userSchema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()->description('The user id, as returned in the `id` field of list-users-tool.')->required(),
        ];
    }
}
