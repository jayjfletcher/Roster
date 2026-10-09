<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * An organization is about to be synced from an external system.
 */
final class OrganizationSyncingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
