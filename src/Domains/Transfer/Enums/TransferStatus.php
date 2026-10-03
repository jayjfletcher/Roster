<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Enums;

enum TransferStatus: string
{
    case Validating = 'validating';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled, self::Expired], true);
    }

    public function label(): string
    {
        return __('roster::roster.transfer_status_'.$this->value);
    }
}
