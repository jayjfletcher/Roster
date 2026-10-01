<?php

declare(strict_types=1);

namespace JayI\Roster\Access;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Impersonation\ImpersonationContext;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;

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
    public function check(?Model $actor, string $permission, Organization|Team|null $scope = null, ?Model $self = null): bool
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
}
