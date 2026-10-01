<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use JayI\Roster\Impersonation\ImpersonationContext;
use JayI\Roster\Impersonation\Impersonator;
use JayI\Roster\Models\Impersonation;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps an impersonated session honest: once the impersonation expires or is
 * ended elsewhere, the impersonator is signed back in on the next request.
 * Registered as `roster.impersonation`.
 */
final class SyncImpersonation
{
    public function __construct(
        private readonly ImpersonationContext $context,
        private readonly Impersonator $impersonator,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Always read the session fresh: long-running processes (Octane,
        // tests) reuse the container between requests.
        $this->context->forget();

        if ($this->context->sessionId() !== null && $this->context->active() === null) {
            $impersonation = Impersonation::query()->find($this->context->sessionId());
            $why = $impersonation?->ended_at === null ? Impersonation::ENDED_EXPIRED : (string) $impersonation->end_reason;

            $this->impersonator->leave($request, $why);
            $request->session()->flash('status', __('roster::roster.impersonation_ended_'.($why === Impersonation::ENDED_EXPIRED ? 'expired' : 'forced')));
        }

        View::share('rosterImpersonation', $this->context->active());

        return $next($request);
    }
}
