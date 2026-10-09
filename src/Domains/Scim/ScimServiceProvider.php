<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim;

use RefactorCircus\Foundation\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Scim\Services\ScimContext;

/**
 * SCIM 2.0 provisioning and the tokens that authenticate it.
 */
class ScimServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ScimContext::class);
    }

    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadRoutesFrom(__DIR__.'/scim.php');
    }
}
