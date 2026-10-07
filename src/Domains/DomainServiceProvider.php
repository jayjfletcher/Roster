<?php

declare(strict_types=1);

namespace JayI\Roster\Domains;

use Illuminate\Support\ServiceProvider;
use JayI\Roster\Domains\Impersonation\ImpersonationServiceProvider;
use JayI\Roster\Domains\Invitation\InvitationServiceProvider;
use JayI\Roster\Domains\Organization\OrganizationServiceProvider;
use JayI\Roster\Domains\Permission\PermissionServiceProvider;
use JayI\Roster\Domains\Role\RoleServiceProvider;
use JayI\Roster\Domains\Scim\ScimServiceProvider;
use JayI\Roster\Domains\Sso\SsoServiceProvider;
use JayI\Roster\Domains\Team\TeamServiceProvider;
use JayI\Roster\Domains\Transfer\TransferServiceProvider;
use JayI\Roster\Domains\User\UserServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * The domain service providers.
     *
     * @var array<int, class-string<ServiceProvider>>
     */
    private array $providers = [
        UserServiceProvider::class,
        OrganizationServiceProvider::class,
        TeamServiceProvider::class,
        InvitationServiceProvider::class,
        RoleServiceProvider::class,
        PermissionServiceProvider::class,
        ImpersonationServiceProvider::class,
        SsoServiceProvider::class,
        ScimServiceProvider::class,
        TransferServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }
}
