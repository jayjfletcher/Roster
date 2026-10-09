{{--
    The banner shown while someone is impersonating, with a button to leave.
    Include it in the application's layout:

        @include('roster::impersonation-banner')

    It renders nothing otherwise. It is standalone, styled inline by Atrium's
    banner component, because it shows on the application's own pages where
    Atrium's stylesheet is not loaded. Pass `bannerClass` to add classes.
--}}
@php($impersonation = $rosterImpersonation ?? app(\RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext::class)->active())

@if ($impersonation)
    @php($directory = app(\RefactorCircus\Roster\Support\Users::class))
    <x-atrium::banner standalone :class="$bannerClass ?? null" data-testid="impersonation-banner">
        {{ __('roster::roster.impersonation_banner', [
            'user' => $impersonation->user ? ($directory->name($impersonation->user) ?? $directory->email($impersonation->user)) : '?',
            'impersonator' => $impersonation->impersonator ? ($directory->name($impersonation->impersonator) ?? $directory->email($impersonation->impersonator)) : '?',
        ]) }}
        · {{ __('roster::roster.impersonation_ends', ['time' => $impersonation->expires_at?->diffForHumans()]) }}

        <x-slot:actions>
            <form method="POST" action="{{ route('roster.impersonation.leave') }}">
                @csrf
                <x-atrium::banner.button standalone data-testid="leave-impersonation">{{ __('roster::roster.impersonation_leave') }}</x-atrium::banner.button>
            </form>
        </x-slot:actions>
    </x-atrium::banner>
@endif
