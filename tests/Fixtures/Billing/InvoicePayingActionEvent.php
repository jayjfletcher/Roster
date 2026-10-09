<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests\Fixtures\Billing;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * Another package's starting event, about a user Roster also knows.
 */
final readonly class InvoicePayingActionEvent implements ActionStartingEvent
{
    public function __construct(public Model $user) {}
}
