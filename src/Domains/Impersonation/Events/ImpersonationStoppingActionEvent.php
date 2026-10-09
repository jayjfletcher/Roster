<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

/**
 * An impersonation is about to end.
 */
final class ImpersonationStoppingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ImpersonationModel $impersonation,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
