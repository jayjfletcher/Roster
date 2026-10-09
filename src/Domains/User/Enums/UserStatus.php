<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Enums;

enum UserStatus: string
{
    case Active = 'active';
    // Waiting for an approver to accept the account.
    case Pending = 'pending';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('roster::roster.status_active'),
            self::Pending => __('roster::roster.status_pending'),
            self::Suspended => __('roster::roster.status_suspended'),
            self::Deactivated => __('roster::roster.status_deactivated'),
        };
    }
}
