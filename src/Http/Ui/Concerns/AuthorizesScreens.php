<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui\Concerns;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;

/**
 * The same permission checks the JSON API and MCP make, for Atrium screens.
 */
trait AuthorizesScreens
{
    private function authorizeScreen(string $permission, Organization|Team|null $scope = null, ?Model $self = null): void
    {
        $user = request()->user();

        abort_unless(
            app(Authorizer::class)->check($user instanceof Model ? $user : null, $permission, $scope, $self),
            403,
        );
    }
}
