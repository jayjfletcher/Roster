{{--
    A status as a coloured dot; its label is the tooltip and accessible name.
    Pass a user, invitation or transfer `status`, or a `variant` and `label`.
--}}
@props([
    'status' => null,
    'variant' => null,
    'label' => null,
])

@php
    $variant ??= \JayI\Roster\Atrium\Badges::forStatus($status);
    $label ??= $status->label();
    // Neutral follows the text colour, so it reads in light and dark mode.
    $color = $variant === 'neutral' ? 'currentColor; opacity: .45' : 'var(--color-'.$variant.')';
@endphp

<x-atrium::tooltip :text="$label">
    <span {{ $attributes->merge(['role' => 'img', 'aria-label' => $label, 'data-status' => $status?->value ?? $variant]) }}
          style="display: inline-block; width: .625rem; height: .625rem; border-radius: 9999px; background-color: {{ $color }}"></span>
</x-atrium::tooltip>
