<?php

declare(strict_types=1);

namespace JayI\Roster\Atrium;

use JayI\Roster\Enums\UserStatus;

/**
 * Atrium badge variants for Roster's enums.
 */
final class Badges
{
    public static function forStatus(UserStatus $status): string
    {
        return match ($status) {
            UserStatus::Active => 'success',
            UserStatus::Suspended => 'warning',
            UserStatus::Deactivated => 'neutral',
        };
    }
}
