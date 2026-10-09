<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium;

use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;

/**
 * Atrium badge variants for Roster's enums.
 */
final class Badges
{
    /**
     * The Atrium colour of a status. Pending - awaiting someone's decision -
     * has its own colour, `info`, used by no other status.
     */
    public static function forStatus(UserStatus|InvitationStatus|TransferStatus $status): string
    {
        return match ($status) {
            UserStatus::Pending, InvitationStatus::Pending, TransferStatus::AwaitingConfirmation => 'info',
            UserStatus::Active, InvitationStatus::Accepted, TransferStatus::Completed => 'success',
            UserStatus::Suspended => 'warning',
            InvitationStatus::Revoked, TransferStatus::Failed => 'danger',
            TransferStatus::Validating, TransferStatus::Running => 'primary',
            UserStatus::Deactivated, InvitationStatus::Declined, InvitationStatus::Expired, TransferStatus::Cancelled, TransferStatus::Expired => 'neutral',
        };
    }
}
