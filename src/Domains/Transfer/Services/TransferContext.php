<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Services;

use JayI\Roster\Domains\Transfer\Models\TransferModel;

/**
 * The import being applied right now, for the audit trail. Bound per
 * request (and per queued job).
 */
final class TransferContext
{
    public ?TransferModel $transfer = null;
}
