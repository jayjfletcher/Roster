<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use JayI\Roster\Actions\EnterImpersonationAction;
use JayI\Roster\Impersonation\Impersonator;
use JayI\Roster\Models\Impersonation;

/**
 * The browser side of impersonation: using the one-time link, and returning
 * to your own account. Leaving ends the record through
 * \JayI\Roster\Actions\StopImpersonationAction (via Impersonator::leave()).
 */
final class ImpersonationWebController
{
    public function __construct(private readonly Impersonator $impersonator) {}

    public function enter(Request $request, string $token): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof Model, 401);

        $impersonation = app(EnterImpersonationAction::class)->execute(
            $request->merge(['token' => $token])->validate(EnterImpersonationAction::rules()),
            $actor,
        );

        $this->impersonator->enter($impersonation, $request);

        return redirect((string) config('roster.impersonation.redirect', '/'));
    }

    public function leave(Request $request): RedirectResponse
    {
        $impersonation = $this->impersonator->leave($request, Impersonation::ENDED_STOPPED);

        return redirect($this->returnTo($impersonation))->with('status', __('roster::roster.impersonation_left'));
    }

    private function returnTo(?Impersonation $impersonation): string
    {
        $configured = config('roster.impersonation.return_to');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $user = $impersonation?->user;

        return $user instanceof Model && Route::has('atrium.roster.users.show')
            ? route('atrium.roster.users.show', $user->getRouteKey())
            : '/';
    }
}
