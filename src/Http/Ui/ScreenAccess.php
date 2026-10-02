<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;

/**
 * Whether the signed-in user may use a permission, asked the way the Atrium
 * screens ask it. Controllers refuse with it and views hide controls with it
 * (as `@rosterCan`), so a control is shown exactly when its action is allowed.
 */
final class ScreenAccess
{
    public static function allows(string $permission, Organization|Team|null $scope = null, ?Model $self = null): bool
    {
        $user = request()->user();

        return app(Authorizer::class)->check($user instanceof Model ? $user : null, $permission, $scope, $self);
    }
}
