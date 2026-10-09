<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

/**
 * An import or export has finished.
 */
final class TransferFinishedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TransferModel $transfer,
    ) {}
}
