<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui\Concerns;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Http\Ui\ScreenAccess;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;

/**
 * The same permission checks the JSON API and MCP make, for Atrium screens.
 */
trait AuthorizesScreens
{
    private function authorizeScreen(string $permission, Organization|Team|null $scope = null, ?Model $self = null): void
    {
        abort_unless(ScreenAccess::allows($permission, $scope, $self), 403);
    }

    /**
     * For lists: without an organization of its own, a user holding the
     * permission only in some organizations still sees those. Returns the
     * organizations to limit the list to, or null for no limit.
     *
     * @return array<int, int|string>|null
     */
    private function authorizeList(string $permission, Organization|Team|null $scope = null, ?Model $self = null): ?array
    {
        if (ScreenAccess::allows($permission, $scope, $self)) {
            return null;
        }

        $organizations = $scope === null ? ScreenAccess::organizations($permission) : [];

        abort_if($organizations === null || $organizations === [], 403);

        return $organizations;
    }
}
