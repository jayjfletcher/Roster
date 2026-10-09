<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;

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
