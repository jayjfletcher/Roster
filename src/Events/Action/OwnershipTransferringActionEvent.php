<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Models\Organization;

/**
 * An organization's ownership is about to move.
 */
final class OwnershipTransferringActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
        public Model $user,
    ) {}
}
