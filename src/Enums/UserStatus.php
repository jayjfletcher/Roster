<?php

declare(strict_types=1);

namespace JayI\Roster\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('roster::roster.status_active'),
            self::Suspended => __('roster::roster.status_suspended'),
            self::Deactivated => __('roster::roster.status_deactivated'),
        };
    }
}
