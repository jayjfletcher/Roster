<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;

/**
 * A pending user has been rejected.
 */
final class UserRejectedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        public ?string $reason,
    ) {}
}
