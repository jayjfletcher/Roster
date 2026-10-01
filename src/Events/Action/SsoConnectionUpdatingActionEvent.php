<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Models\SsoConnection;

/**
 * An SSO connection is about to be updated.
 */
final class SsoConnectionUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SsoConnection $connection,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
