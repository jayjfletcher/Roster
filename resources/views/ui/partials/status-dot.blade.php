{{--
    A user, invitation or transfer status as Atrium's status dot, coloured by
    JayI\Roster\Atrium\Badges (pending is info). Or pass `variant` and `label`.
    Callers pass every key (`status`, `variant`, `label`, `testid`), null when
    unused, so variables of the including view never leak in.
--}}
<x-atrium::status-dot
    :variant="$variant ?? \JayI\Roster\Atrium\Badges::forStatus($status)"
    :label="$label ?? $status->label()"
    :data-status="$status?->value ?? $variant"
    :data-testid="$testid" />
