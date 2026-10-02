<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;

/**
 * An import template is about to be shown.
 */
final class ImportTemplateShowingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $type,
    ) {}
}
