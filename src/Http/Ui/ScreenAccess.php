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

    /**
     * The organizations the signed-in user holds a permission in; null when
     * they hold it globally.
     *
     * @return array<int, int|string>|null
     */
    public static function organizations(string $permission): ?array
    {
        $user = request()->user();

        return app(Authorizer::class)->organizationsWith($user instanceof Model ? $user : null, $permission);
    }

    /**
     * Whether the signed-in user holds a permission globally or in any
     * organization: enough to open a list limited to those organizations.
     */
    public static function anywhere(string $permission): bool
    {
        return self::allows($permission) || self::organizations($permission) !== [];
    }
}
