<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Models\Organization;

/**
 * An organization has been linked to an external record.
 */
final class OrganizationLinkedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $source,
        public string $external_id,
        public ?string $account_number,
    ) {}
}
