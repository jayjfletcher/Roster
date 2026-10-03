<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use JayI\Roster\Domains\Organization\Models\MembershipModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\User\Http\Middleware\EnsureUserIsActive;
use JayI\Roster\Domains\User\Listeners\ApplyRegistrationStatus;
use JayI\Roster\Domains\User\Models\ProfileModel;
use JayI\Roster\Domains\User\Models\UserModel;
use JayI\Roster\Support\ServiceProvider;

/**
 * Users and their profiles, status and approval.
 */
class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Roster\Models\User' => UserModel::class,
            'JayI\Roster\Models\Profile' => ProfileModel::class,
        ]);

        $this->registerProfileRelation();

        $this->app->make(Router::class)->aliasMiddleware('roster.active', EnsureUserIsActive::class);
        $this->app->make(Dispatcher::class)->listen(Registered::class, ApplyRegistrationStatus::class);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }

    /**
     * Give a user model without the HasRoster trait the same relations, so
     * Roster works on any model untouched.
     */
    private function registerProfileRelation(): void
    {
        $model = $this->app->make(Repository::class)->get('roster.users.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class) || method_exists($model, 'rosterProfile')) {
            return;
        }

        $model::resolveRelationUsing(
            'rosterProfile',
            fn (Model $user) => $user->hasOne(ProfileModel::class, 'user_id'),
        );

        $model::resolveRelationUsing(
            'rosterMemberships',
            fn (Model $user) => $user->hasMany(MembershipModel::class, 'user_id'),
        );

        $model::resolveRelationUsing(
            'rosterRoleAssignments',
            fn (Model $user) => $user->hasMany(RoleAssignmentModel::class, 'user_id'),
        );

        $model::resolveRelationUsing(
            'rosterOrganizations',
            fn (Model $user) => $user->belongsToMany(OrganizationModel::class, 'roster_memberships', 'user_id', 'organization_id')->withTimestamps(),
        );
    }
}
