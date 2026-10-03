<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use JayI\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use JayI\Roster\Domains\Organization\Actions\AddMemberAction;
use JayI\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use JayI\Roster\Domains\Permission\Actions\CreatePermissionAction;
use JayI\Roster\Domains\Role\Actions\AssignRoleAction;
use JayI\Roster\Domains\Role\Actions\CreateRoleAction;
use JayI\Roster\Domains\Role\Models\RoleModel;
use JayI\Roster\Domains\Team\Actions\AddTeamMemberAction;
use JayI\Roster\Domains\Team\Actions\CreateTeamAction;
use JayI\Roster\Domains\User\Actions\CreateUserAction;
use JayI\Roster\Domains\User\Actions\SuspendUserAction;
use Workbench\Database\Factories\UserFactory;

/**
 * Demo data, built through Roster's own Actions.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Invitation emails go nowhere in the demo.
        Notification::fake();

        $admin = UserFactory::new()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        Artisan::call('roster:grant-super-admin', ['email' => 'admin@example.com']);

        $ada = UserFactory::new()->create(['name' => 'Ada Lovelace', 'email' => 'ada@acme.test']);
        $grace = UserFactory::new()->create(['name' => 'Grace Hopper', 'email' => 'grace@acme.test']);
        $alan = UserFactory::new()->create(['name' => 'Alan Turing', 'email' => 'alan@globex.test']);
        $eve = UserFactory::new()->create(['name' => 'Eve Example', 'email' => 'eve@example.com']);

        $acme = app(CreateOrganizationAction::class)->execute(['name' => 'Acme', 'domains' => ['acme.test']], $ada);
        $globex = app(CreateOrganizationAction::class)->execute(['name' => 'Globex'], $alan);

        app(AddMemberAction::class)->execute($acme, ['user' => $grace->getRouteKey()]);
        app(AddMemberAction::class)->execute($acme, ['user' => $admin->getRouteKey()]);

        $engineering = app(CreateTeamAction::class)->execute($acme, ['name' => 'Engineering']);
        app(CreateTeamAction::class)->execute($acme, ['name' => 'Support']);
        app(CreateTeamAction::class)->execute($globex, ['name' => 'Research']);

        app(AddTeamMemberAction::class)->execute($engineering, ['user' => $grace->getRouteKey()]);
        app(AssignRoleAction::class)->execute($grace, [
            'role' => RoleModel::query()->where('slug', 'lead')->value('id'),
            'organization' => $acme->slug,
            'team' => $engineering->slug,
        ]);

        app(CreateInvitationAction::class)->execute($acme, ['email' => 'new.hire@acme.test', 'teams' => ['support']], $ada);

        app(CreatePermissionAction::class)->execute(['name' => 'invoices.edit', 'description' => 'Edit invoices.']);
        $billing = app(CreateRoleAction::class)->execute([
            'name' => 'Billing',
            'scope' => 'organization',
            'permissions' => ['invoices.edit', 'roster.members.view'],
        ]);
        app(AssignRoleAction::class)->execute($grace, ['role' => $billing->id, 'organization' => $acme->slug]);

        app(SuspendUserAction::class)->execute($eve, ['reason' => 'Demo: a suspended account.']);

        // Demo: an account awaiting approval, also on Acme's Members tab.
        $pat = app(CreateUserAction::class)->execute(['name' => 'Pat Pending', 'email' => 'pat@acme.test', 'status' => 'pending']);
        app(AddMemberAction::class)->execute($acme, ['user' => $pat->getRouteKey()]);
    }
}
