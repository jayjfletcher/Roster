{{--
    A user, invitation or transfer status as Atrium's status dot, coloured by
    JayI\Roster\Atrium\Badges (pending is info). Or pass `variant` and `label`.
--}}
@props([
    'status' => null,
    'variant' => null,
    'label' => null,
])

<x-atrium::status-dot
    :variant="$variant ?? \JayI\Roster\Atrium\Badges::forStatus($status)"
    :label="$label ?? $status->label()"
    {{ $attributes->merge(['data-status' => $status?->value ?? $variant]) }} />
