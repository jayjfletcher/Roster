<?php

declare(strict_types=1);

namespace JayI\Roster\Atrium;

use JayI\Cortex\CortexServiceProvider;

/**
 * Whether the screens offer MCP redirect domains for organizations and
 * users: only while jayi/cortex is installed and loaded, and its stored
 * domains count (`cortex.redirect_domains.enabled`). Cortex keeps them and
 * accepts them when an MCP client registers; no Cortex class is touched
 * unless this is true.
 */
final class RedirectDomains
{
    public static function available(): bool
    {
        return class_exists(CortexServiceProvider::class)
            && app()->getProvider(CortexServiceProvider::class) !== null
            && (bool) config('cortex.redirect_domains.enabled', true);
    }
}
