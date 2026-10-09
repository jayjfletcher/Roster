<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * An import or export is about to start.
 */
final class TransferStartingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
