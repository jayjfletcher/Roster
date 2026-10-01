<?php

declare(strict_types=1);

namespace JayI\Roster\Scim;

use JayI\Roster\Models\ScimToken;
use SensitiveParameter;

/**
 * A freshly issued SCIM token. `$plain` is the only copy: show it once.
 */
final readonly class IssuedScimToken
{
    public function __construct(
        public ScimToken $token,
        #[SensitiveParameter]
        public string $plain,
    ) {}
}
