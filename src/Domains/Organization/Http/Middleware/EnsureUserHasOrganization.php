<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JayI\Roster\Roster;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects users who belong to no organization. Registered as
 * `roster.organization`.
 */
final class EnsureUserHasOrganization
{
    public function __construct(private readonly Roster $roster) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Model && $this->roster->organization($user) === null) {
            abort(403, __('roster::roster.no_organization'));
        }

        return $next($request);
    }
}
