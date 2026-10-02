<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use JayI\Atrium\Navigation\NavigationRegistry;
use JayI\Atrium\Navigation\NavItem;
use JayI\Atrium\Search\SearchRegistry;
use JayI\Atrium\Search\SearchResult;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Mcp\Tools\ListOrganizationsTool;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Transfer;
use Workbench\App\Models\User;

/**
 * Acme and Globex, each with its own role, and an admin of Acme only.
 *
 * @return array{acme: Organization, globex: Organization, admin: User}
 */
function twoOrganizations(): array
{
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);

    Role::factory()->create(['scope' => 'organization', 'organization_id' => $acme->id, 'name' => 'Acme billing', 'slug' => 'acme-billing']);
    Role::factory()->create(['scope' => 'organization', 'organization_id' => $globex->id, 'name' => 'Globex billing', 'slug' => 'globex-billing']);

    $admin = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $admin->getRouteKey()]);
    grant($admin, 'admin', $acme);

    return ['acme' => $acme, 'globex' => $globex, 'admin' => $admin];
}

/**
 * @return array<int, string>
 */
function rosterNavFor(User $user): array
{
    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): User => $user);

    return array_map(fn (NavItem $item): string => $item->label, app(NavigationRegistry::class)->items($request));
}

it('finds the organizations a user holds a permission in', function (): void {
    ['acme' => $acme, 'admin' => $admin] = twoOrganizations();
    $owner = user();
    $owned = organization($owner, ['name' => 'Owned']);
    $super = user();
    grant($super, 'super-admin');

    expect(app(Permissions::class)->organizationsWith($admin, 'roster.audit.view'))->toBe([$acme->id])
        ->and(app(Permissions::class)->organizationsWith($admin, 'roster.users.view'))->toBe([])
        ->and(app(Permissions::class)->organizationsWith($owner, 'roster.audit.view'))->toBe([$owned->id])
        // Owning an organization never grants a global-only permission.
        ->and(app(Permissions::class)->organizationsWith($owner, 'roster.users.view'))->toBe([])
        ->and(app(Permissions::class)->organizationsWith($super, 'roster.audit.view'))->toBeNull();
});

it('shows organization admins the navigation their organization permissions open', function (): void {
    ['admin' => $admin] = twoOrganizations();
    openAtriumFor($admin);

    expect(rosterNavFor($admin))
        ->toContain('Organizations', 'Roles', 'Imports & exports', 'Audit log')
        ->not->toContain('Users', 'Permissions', 'Impersonations');
});

it('hides organization navigation from users without any organization permission', function (): void {
    $member = user();
    openAtriumFor($member);

    expect(rosterNavFor($member))->not->toContain('Organizations', 'Roles', 'Audit log');
});

it('lists only their own organizations to organization admins, on every surface', function (): void {
    ['admin' => $admin] = twoOrganizations();
    openAtriumFor($admin);
    $this->actingAs($admin);

    $this->get(route('atrium.roster.organizations.index'))->assertOk()->assertSee('Acme')->assertDontSee('Globex');

    expect(collect($this->getJson(route('roster.organizations.index'))->assertOk()->json('data'))->pluck('slug')->all())->toBe(['acme']);

    mcpTool(ListOrganizationsTool::class)->assertOk()->assertSee('Acme')->assertDontSee('Globex');
});

it('still refuses an organization the admin may not see', function (): void {
    ['admin' => $admin] = twoOrganizations();
    openAtriumFor($admin);
    $this->actingAs($admin);

    $this->get(route('atrium.roster.roles.index', ['organization' => 'globex']))->assertForbidden();
    $this->getJson(route('roster.roles.index', ['organization' => 'globex']))->assertForbidden();
});

it('lists shared roles and their organizations own roles', function (): void {
    ['admin' => $admin] = twoOrganizations();
    openAtriumFor($admin);
    $this->actingAs($admin);

    $this->get(route('atrium.roster.roles.index'))->assertOk()
        ->assertSee('Acme billing')
        ->assertDontSee('Globex billing')
        ->assertSee('Member');

    $names = collect($this->getJson(route('roster.roles.index'))->assertOk()->json('data'))->pluck('name');

    expect($names)->toContain('Acme billing', 'Member')->not->toContain('Globex billing');
});

it('lists their own transfers and their organizations transfers', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'admin' => $admin] = twoOrganizations();
    openAtriumFor($admin);

    $ownId = Transfer::factory()->create(['type' => 'export_users', 'requested_by' => $admin->getKey(), 'organization_id' => null])->id;
    $acmeId = Transfer::factory()->create(['type' => 'export_members', 'requested_by' => user()->getKey(), 'organization_id' => $acme->id])->id;
    $globexId = Transfer::factory()->create(['type' => 'export_members', 'requested_by' => user()->getKey(), 'organization_id' => $globex->id])->id;

    $ids = collect($this->actingAs($admin)->getJson(route('roster.transfers.index'))->assertOk()->json('data'))->pluck('id')->all();

    expect($ids)->toContain($ownId, $acmeId)->not->toContain($globexId);
});

it('searches only the organizations the searcher may view', function (): void {
    ['admin' => $admin] = twoOrganizations();
    openAtriumFor($admin);
    $this->actingAs($admin);
    config()->set('atrium.search.concurrency', 'sync');

    $request = Request::create('/atrium/search');
    $request->setUserResolver(fn (): User => $admin);

    $titles = array_map(fn (SearchResult $result): string => $result->title, app(SearchRegistry::class)->search($request, 'e'));

    expect($titles)->toContain('Acme')->not->toContain('Globex');
});

/**
 * Let a user open Atrium, as Roster's `atrium.view` permission does.
 */
function openAtriumFor(User $user): void
{
    RoleAssignment::query()->create(['role_id' => roleWith(['atrium.view'])->id, 'user_id' => $user->getKey()]);
    app(Permissions::class)->flush();
}
