<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Role\Models\RoleModel;

/**
 * A role is about to be assigned.
 */
final class RoleAssigningActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        public RoleModel $role,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
