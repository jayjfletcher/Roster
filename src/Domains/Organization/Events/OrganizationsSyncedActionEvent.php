<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;

/**
 * A batch of organizations has been synced.
 */
final class OrganizationsSyncedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        /** @var array<string, int> */
        public array $summary,
    ) {}
}
