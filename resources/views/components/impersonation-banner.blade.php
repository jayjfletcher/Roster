@php($impersonation = $rosterImpersonation ?? app(\JayI\Roster\Domains\Impersonation\Services\ImpersonationContext::class)->active())

@if ($impersonation)
    @php($directory = app(\JayI\Roster\Support\Users::class))
    <div {{ $attributes->merge(['class' => 'roster-impersonation-banner', 'role' => 'status']) }}
         style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;padding:.5rem 1rem;background:#7c2d12;color:#fff;font:500 .875rem/1.4 system-ui,sans-serif;"
         data-testid="impersonation-banner">
        <span>
            {{ __('roster::roster.impersonation_banner', [
                'user' => $impersonation->user ? ($directory->name($impersonation->user) ?? $directory->email($impersonation->user)) : '?',
                'impersonator' => $impersonation->impersonator ? ($directory->name($impersonation->impersonator) ?? $directory->email($impersonation->impersonator)) : '?',
            ]) }}
            · {{ __('roster::roster.impersonation_ends', ['time' => $impersonation->expires_at?->diffForHumans()]) }}
        </span>
        <form method="POST" action="{{ route('roster.impersonation.leave') }}" style="margin:0">
            @csrf
            <button type="submit" data-testid="leave-impersonation" style="font:inherit;padding:.25rem .75rem;border:1px solid #fff;border-radius:.375rem;background:transparent;color:inherit;cursor:pointer">
                {{ __('roster::roster.impersonation_leave') }}
            </button>
        </form>
    </div>
@endif
