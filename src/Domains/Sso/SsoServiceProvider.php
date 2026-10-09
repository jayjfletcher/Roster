<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Foundation\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Sso\Services\Sso;

/**
 * Single sign-on connections (OIDC, SAML, Entra ID) and linked identities.
 */
class SsoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');

        if (! $this->app->make(Sso::class)->available()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/web.php');

        // SAML identity providers post the signed assertion cross-site;
        // the assertion's signature, not a CSRF token, protects it.
        ValidateCsrfToken::except([trim((string) $this->app->make(Repository::class)->get('roster.sso.prefix', 'roster/sso'), '/').'/*/callback']);
    }
}
