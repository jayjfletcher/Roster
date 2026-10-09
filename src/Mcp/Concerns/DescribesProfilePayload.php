<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Mcp\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

trait DescribesProfilePayload
{
    /**
     * @return array<string, Type>
     */
    protected function profileSchema(JsonSchema $schema): array
    {
        return [
            'display_name' => $schema->string()->description('Name shown in place of the account name. Null clears it.'),
            'avatar_url' => $schema->string()->description('Absolute URL of the avatar image. Null clears it.'),
            'timezone' => $schema->string()->description('IANA timezone, e.g. "Europe/London".'),
            'locale' => $schema->string()->description('Locale code, e.g. "en" or "en_GB".'),
            'bio' => $schema->string()->description('Free-text biography, up to 5000 characters.'),
            'meta' => $schema->object()->description('Arbitrary key/value data for the host app. Replaces the whole object.'),
        ];
    }
}
