<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;
use JayI\Roster\Domains\Sso\Models\SsoIdentityModel;
use JayI\Roster\Domains\Sso\Services\Sso;
use JayI\Roster\Support\ServiceProvider;

/**
 * Single sign-on connections (OIDC, SAML, Entra ID) and linked identities.
 */
class SsoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Roster\Models\SsoConnection' => SsoConnectionModel::class,
            'JayI\Roster\Models\SsoIdentity' => SsoIdentityModel::class,
        ]);

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
