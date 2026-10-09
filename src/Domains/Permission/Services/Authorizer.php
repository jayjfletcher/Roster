<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * How Roster's own surfaces - the JSON API, MCP tools and Atrium screens -
 * decide whether the caller may act.
 *
 * With `roster.authorization` on (the default), every call needs an
 * authenticated user holding the Action's permission in the right scope.
 * Turning it off leaves the route middleware as the only check.
 */
final class Authorizer
{
    public function __construct(
        private readonly Repository $config,
        private readonly Permissions $permissions,
    ) {}

    public function enabled(): bool
    {
        return $this->config->get('roster.authorization', true) !== false;
    }

    /**
     * Whether `$actor` may use a permission in a scope. `$self` names the user
     * the call is about, for abilities everyone has over themselves.
     */
    public function check(?Model $actor, string $permission, OrganizationModel|TeamModel|null $scope = null, ?Model $self = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if ($actor === null) {
            return false;
        }

        if (app(ImpersonationContext::class)->isBlocked($permission)) {
            return false;
        }

        if ($self !== null && $actor->is($self)) {
            return true;
        }

        return $this->permissions->allows($actor, $permission, $scope);
    }

    /**
     * The organizations `$actor` may use a permission in, for lists that show
     * what falls within them. Null means no organization limits them: they
     * hold it globally, or authorization is off.
     *
     * @return array<int, int|string>|null
     */
    public function organizationsWith(?Model $actor, string $permission): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        if ($actor === null || app(ImpersonationContext::class)->isBlocked($permission)) {
            return [];
        }

        return $this->permissions->organizationsWith($actor, $permission);
    }
}
