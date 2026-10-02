{{--
    Atrium ships one precompiled stylesheet built from its own views, so
    Tailwind utilities that only Roster's screens use don't exist there.
    These fill that gap, plus `roster-actions`, which lines a row's buttons
    up with its inputs (below the labels) whatever hints or errors follow.
    tests/Feature/Ui/StylesTest.php keeps this list complete.
--}}
@pushOnce('atrium-head')
<style>
    .w-40 { width: 10rem; }
    .w-48 { width: 12rem; }
    .w-56 { width: 14rem; }
    .w-72 { width: 18rem; }
    .w-80 { width: 20rem; }
    .max-w-2xl { max-width: 42rem; }
    .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .col-span-2 { grid-column: span 2 / span 2; }
    .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace; }
    .break-all { word-break: break-all; }
    .leading-relaxed { line-height: 1.625; }
    .mb-1 { margin-bottom: 0.25rem; }
    .ms-auto { margin-inline-start: auto; }
    .underline-offset-2 { text-underline-offset: 2px; }
    .hover\:underline:hover { text-decoration-line: underline; }
    .roster-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; padding-top: 1.625rem; }
    @media (min-width: 40rem) {
        .sm\:col-span-2 { grid-column: span 2 / span 2; }
    }
    @media (min-width: 64rem) {
        .lg\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lg\:col-span-2 { grid-column: span 2 / span 2; }
    }
</style>
@endPushOnce
