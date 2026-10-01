<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

trait DescribesTeam
{
    use DescribesOrganization;

    /**
     * @return array<string, Type>
     */
    protected function teamSchema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'team' => $schema->string()->description('The team slug, unique within its organization.')->required(),
        ];
    }
}
