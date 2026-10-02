{{--
    An icon-only button or link. The label is its accessible name and shows
    as a tooltip. `icon` names one of JayI\Roster\Atrium\Icons.
--}}
@props([
    'icon',
    'label',
    'href' => null,
    'variant' => 'ghost',
    'size' => 'md',
    'type' => 'button',
])

<x-atrium::tooltip :text="$label" position="bottom">
    <x-atrium::button :variant="$variant" :size="$size" :type="$type" :href="$href" {{ $attributes->merge(['aria-label' => $label])->class(['roster-icon-button', 'roster-icon-button-sm' => $size === 'sm']) }}>
        {!! \JayI\Roster\Atrium\Icons::svg($icon) !!}
    </x-atrium::button>
</x-atrium::tooltip>
