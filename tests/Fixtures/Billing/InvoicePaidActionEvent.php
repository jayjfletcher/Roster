<?php

declare(strict_types=1);

namespace JayI\Roster\Tests\Fixtures\Billing;

use Illuminate\Database\Eloquent\Model;
use JayI\Foundation\Contracts\ActionFinishedEvent;

/**
 * Another package's finished event, about a user Roster also knows.
 */
final readonly class InvoicePaidActionEvent implements ActionFinishedEvent
{
    public function __construct(public Model $user) {}
}
