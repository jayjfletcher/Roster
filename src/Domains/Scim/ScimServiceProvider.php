<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim;

use JayI\Foundation\Support\ServiceProvider;
use JayI\Roster\Domains\Scim\Models\ScimGroupModel;
use JayI\Roster\Domains\Scim\Models\ScimTokenModel;
use JayI\Roster\Domains\Scim\Models\ScimUserModel;
use JayI\Roster\Domains\Scim\Services\ScimContext;

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
        $this->keepMorphAliases([
            'JayI\Roster\Models\ScimGroup' => ScimGroupModel::class,
            'JayI\Roster\Models\ScimToken' => ScimTokenModel::class,
            'JayI\Roster\Models\ScimUser' => ScimUserModel::class,
        ]);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadRoutesFrom(__DIR__.'/scim.php');
    }
}
