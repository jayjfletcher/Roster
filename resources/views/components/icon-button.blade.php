{{--
    An icon-only button or link. The label is its accessible name and shows
    as a tooltip. Icons: `transfers` (import / export) and `new`.
--}}
@props([
    'icon',
    'label',
    'href' => null,
    'variant' => 'ghost',
])

<x-atrium::tooltip :text="$label" position="bottom">
    <x-atrium::button :variant="$variant" :href="$href" {{ $attributes->merge(['aria-label' => $label, 'class' => 'roster-icon-button']) }}>
        @if ($icon === 'transfers')
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" /></svg>
        @elseif ($icon === 'new')
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        @endif
    </x-atrium::button>
</x-atrium::tooltip>
