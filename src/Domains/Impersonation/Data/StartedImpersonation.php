<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation\Data;

use JayI\Roster\Domains\Impersonation\Models\ImpersonationModel;
use SensitiveParameter;

/**
 * A freshly issued impersonation and its one-time link. The link holds the
 * only copy of the token: show it once.
 */
final readonly class StartedImpersonation
{
    public function __construct(
        public ImpersonationModel $impersonation,
        #[SensitiveParameter]
        public string $url,
    ) {}
}
