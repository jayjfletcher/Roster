<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization;

use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use JayI\Foundation\Support\ServiceProvider;
use JayI\Roster\Domains\Organization\Http\Middleware\EnsureUserHasOrganization;
use JayI\Roster\Domains\Organization\Listeners\JoinOrganizationsOnVerified;
use JayI\Roster\Domains\Organization\Models\MembershipModel;
use JayI\Roster\Domains\Organization\Models\OrganizationDomainModel;
use JayI\Roster\Domains\Organization\Models\OrganizationLinkModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Organizations, their members, domains, external links and the current context.
 */
class OrganizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Roster\Models\Organization' => OrganizationModel::class,
            'JayI\Roster\Models\OrganizationDomain' => OrganizationDomainModel::class,
            'JayI\Roster\Models\OrganizationLink' => OrganizationLinkModel::class,
            'JayI\Roster\Models\Membership' => MembershipModel::class,
        ]);

        $this->app->make(Router::class)->aliasMiddleware('roster.organization', EnsureUserHasOrganization::class);
        $this->app->make(Dispatcher::class)->listen(Verified::class, JoinOrganizationsOnVerified::class);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
