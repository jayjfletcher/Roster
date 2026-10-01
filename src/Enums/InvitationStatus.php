<?php

declare(strict_types=1);

namespace JayI\Roster\Enums;

enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function label(): string
    {
        return __('roster::roster.invitation_'.($this === self::Revoked ? 'revoked_status' : $this->value));
    }
}
