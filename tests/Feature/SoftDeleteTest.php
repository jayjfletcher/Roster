<?php

declare(strict_types=1);

require_once __DIR__.'/Scim/helpers.php';
require_once __DIR__.'/Sso/helpers.php';

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Organization\Actions\AddMemberAction;
use JayI\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use JayI\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\RestoreOrganizationAction;
use JayI\Roster\Domains\Organization\Actions\SwitchContextAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;
use JayI\Roster\Domains\User\Actions\DeleteUserAction;
use JayI\Roster\Domains\User\Actions\PurgeUserAction;
use JayI\Roster\Roster;
use Workbench\App\Models\User;

it('stops a deleted user signing in, and keeps them out of member lists', function (): void {
    // As in an app, sign-in uses the same (soft-deleting) user model.
    config()->set('auth.providers.users.model', User::class);
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user(['email' => 'ada@example.com']);
    app(AddMemberAction::class)->execute($acme, ['user' => $ada->getRouteKey()]);

    app(DeleteUserAction::class)->execute($ada);

    expect(Auth::guard('web')->attempt(['email' => 'ada@example.com', 'password' => 'password']))->toBeFalse();
    $this->actingAs(user())->getJson(route('roster.organizations.members.index', 'acme'))->assertJsonCount(1, 'data');
});

it('turns a deleted organization off everywhere until it is restored', function (): void {
    [$acme, $token] = scimOrg();
    $acme->update(['auto_join' => true]);
    $member = user(['email' => 'member@example.com']);
    app(AddMemberAction::class)->execute($acme, ['user' => $member->getRouteKey()]);
    app(SwitchContextAction::class)->execute($member, ['organization' => 'acme']);
    SsoConnectionModel::factory()->create(['organization_id' => $acme->id, 'slug' => 'acme-okta']);

    app(DeleteOrganizationAction::class)->execute($acme);
    app()->forgetScopedInstances();

    $this->actingAs(user())->getJson(route('roster.organizations.show', 'acme'))->assertNotFound();
    $this->getJson('/scim/v2/acme/Users', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();

    expect(SsoConnectionModel::query()->where('slug', 'acme-okta')->exists())->toBeFalse()
        ->and(app(Roster::class)->organization($member->fresh()))->toBeNull()
        ->and(app(JoinOrganizationsByDomainAction::class)->execute(user(['email' => 'new@acme.test', 'email_verified_at' => now()])))->toHaveCount(0);

    // Its slug and domains stay reserved.
    expect(fn () => validator(['name' => 'Acme', 'slug' => 'acme', 'owner' => user()->getRouteKey()], CreateOrganizationAction::rules())->validate())->toThrow(ValidationException::class)
        ->and(fn () => validator(['name' => 'New', 'domains' => ['acme.test']], CreateOrganizationAction::rules())->validate())->toThrow(ValidationException::class);

    app(RestoreOrganizationAction::class)->execute(OrganizationModel::withTrashed()->sole());
    app()->forgetScopedInstances();

    expect(app(Roster::class)->organization($member->fresh())?->slug)->toBe('acme');
    $this->getJson('/scim/v2/acme/Users', ['Authorization' => 'Bearer '.$token])->assertOk();
});

it('only purges deleted records', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();

    expect(fn () => app(PurgeOrganizationAction::class)->execute($acme))->toThrow(ValidationException::class, 'Delete it first')
        ->and(fn () => app(PurgeUserAction::class)->execute($ada))->toThrow(ValidationException::class, 'Delete it first');

    app(DeleteOrganizationAction::class)->execute($acme);
    app(PurgeOrganizationAction::class)->execute(OrganizationModel::withTrashed()->sole());

    expect(OrganizationModel::withTrashed()->count())->toBe(0);
});

it('purges records deleted longer ago than the retention period', function (): void {
    $old = organization(attributes: ['name' => 'Old']);
    $recent = organization(attributes: ['name' => 'Recent']);
    $gone = user();

    $this->travelTo(now()->subDays(40));
    app(DeleteOrganizationAction::class)->execute($old);
    app(DeleteUserAction::class)->execute($gone);
    $this->travelBack();
    app(DeleteOrganizationAction::class)->execute($recent);

    // Null retention keeps deleted records forever.
    $this->artisan('roster:purge-deleted')->expectsOutputToContain('forever')->assertSuccessful();
    expect(OrganizationModel::onlyTrashed()->count())->toBe(2);

    config()->set('roster.deletes.retention_days', 30);
    $this->artisan('roster:purge-deleted')->expectsOutputToContain('Purged 1 users')->assertSuccessful();

    expect(OrganizationModel::onlyTrashed()->pluck('name')->all())->toBe(['Recent'])
        ->and(User::withTrashed()->whereKey($gone->getKey())->exists())->toBeFalse();

    $this->artisan('roster:purge-deleted', ['--days' => 0])->assertSuccessful();
    expect(OrganizationModel::onlyTrashed()->count())->toBe(0);
});

it('restores and purges from atrium, and lists deleted records', function (): void {
    $this->actingAs($admin = user());
    $pat = user(['name' => 'Pat Gone', 'email' => 'pat@example.com']);
    $globex = organization(attributes: ['name' => 'Globex']);

    $this->delete(route('atrium.roster.users.destroy', $pat->getRouteKey()), ['confirm' => '1']);
    $this->delete(route('atrium.roster.organizations.destroy', 'globex'), ['confirm' => '1']);

    $this->get(route('atrium.roster.users.index', ['trashed' => 'only']))->assertOk()->assertSee('Pat Gone')->assertSee('data-testid="restore-user"', false);
    $this->get(route('atrium.roster.organizations.index', ['trashed' => 'only']))->assertOk()->assertSee('Globex')->assertSee('data-testid="restore-organization"', false);
    $this->get(route('atrium.roster.users.show', $pat->getRouteKey()))->assertOk()->assertSee('data-testid="deleted-banner"', false)->assertSee('Delete permanently');
    $this->get(route('atrium.roster.organizations.show', 'globex'))->assertOk()->assertSee('data-testid="deleted-banner"', false);

    $this->post(route('atrium.roster.users.restore', $pat->getRouteKey()))->assertRedirect();
    expect($pat->fresh()->trashed())->toBeFalse();

    $this->delete(route('atrium.roster.organizations.purge', 'globex'))->assertSessionHasErrors('confirm');
    $this->delete(route('atrium.roster.organizations.purge', 'globex'), ['confirm' => '1'])->assertRedirect();
    expect(OrganizationModel::withTrashed()->where('slug', 'globex')->exists())->toBeFalse();
});
