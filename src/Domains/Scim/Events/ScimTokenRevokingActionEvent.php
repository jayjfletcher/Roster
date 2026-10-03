<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Scim\Models\ScimTokenModel;

/**
 * A SCIM token is about to be revoked.
 */
final class ScimTokenRevokingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ScimTokenModel $token,
    ) {}
}
