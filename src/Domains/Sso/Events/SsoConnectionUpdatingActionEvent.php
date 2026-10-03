<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * An SSO connection is about to be updated.
 */
final class SsoConnectionUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SsoConnectionModel $connection,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
