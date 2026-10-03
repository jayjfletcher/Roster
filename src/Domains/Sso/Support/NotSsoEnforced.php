<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Route;
use JayI\Roster\Roster;

/**
 * For the app's password login form: fails when the email's organization
 * requires single sign-on, pointing the user at it.
 *
 *     'email' => ['required', 'email', new NotSsoEnforced],
 */
final class NotSsoEnforced implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $connection = app(Roster::class)->ssoRequiredFor($value);

        if ($connection === null) {
            return;
        }

        $fail(__('roster::roster.sso_required', [
            'organization' => $connection->organization?->name,
            'url' => Route::has('roster.sso.start') ? route('roster.sso.start', $connection->slug) : '',
        ]));
    }
}
