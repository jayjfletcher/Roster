<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Transfer\Models\TransferModel;

/**
 * An import has been confirmed and is being applied.
 */
final class TransferConfirmedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TransferModel $transfer,
    ) {}
}
