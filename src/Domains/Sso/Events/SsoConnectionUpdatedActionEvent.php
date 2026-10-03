<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * An SSO connection has been updated.
 */
final class SsoConnectionUpdatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SsoConnectionModel $connection,
    ) {}
}
