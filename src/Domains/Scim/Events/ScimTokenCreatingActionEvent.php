<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * A SCIM token is about to be issued.
 */
final class ScimTokenCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
