<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JayI\Roster\Domains\User\Enums\UserStatus;
use JayI\Roster\Support\Users;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects suspended and deactivated users, and those awaiting approval. Registered as `roster.active`.
 */
final class EnsureUserIsActive
{
    public function __construct(private readonly Users $users) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Model) {
            $status = $this->users->status($user);

            if ($status === UserStatus::Pending) {
                abort(403, __('roster::roster.account_pending'));
            }

            if ($status !== UserStatus::Active) {
                abort(403, __('roster::roster.account_inactive'));
            }
        }

        return $next($request);
    }
}
