<?php

declare(strict_types=1);

namespace JayI\Roster\Impersonation;

use JayI\Roster\Models\Impersonation;
use SensitiveParameter;

/**
 * A freshly issued impersonation and its one-time link. The link holds the
 * only copy of the token: show it once.
 */
final readonly class StartedImpersonation
{
    public function __construct(
        public Impersonation $impersonation,
        #[SensitiveParameter]
        public string $url,
    ) {}
}
