<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers;

use JayI\Roster\Models\Transfer;

/**
 * The import being applied right now, for the audit trail. Bound per
 * request (and per queued job).
 */
final class TransferContext
{
    public ?Transfer $transfer = null;
}
