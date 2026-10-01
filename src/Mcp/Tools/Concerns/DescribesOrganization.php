<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

trait DescribesOrganization
{
    /**
     * @return array<string, Type>
     */
    protected function organizationSchema(JsonSchema $schema): array
    {
        return [
            'organization' => $schema->string()->description('The organization slug.')->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    protected function organizationSettingsSchema(JsonSchema $schema): array
    {
        return [
            'auto_join' => $schema->boolean()->description('Let users who verify an email on one of the domains join automatically. Defaults to false.'),
            'domains' => $schema->array()->items($schema->string())->description('Email domains for auto-join, e.g. ["example.com"]. Replaces the whole list. A domain can belong to one organization only.'),
        ];
    }
}
