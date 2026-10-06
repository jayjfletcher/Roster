<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Scim\Models\ScimTokenModel;

/**
 * A SCIM token has been issued.
 */
final class ScimTokenCreatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ScimTokenModel $token,
    ) {}
}
