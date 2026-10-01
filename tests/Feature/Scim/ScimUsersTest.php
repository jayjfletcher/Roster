<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\CreateScimTokenAction;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\ScimUser;
use JayI\Roster\Models\SsoConnection;
use JayI\Roster\Models\SsoIdentity;
use JayI\Roster\Support\Users;
use Workbench\App\Models\User;

beforeEach(function (): void {
    [$this->acme, $this->scimToken] = scimOrg();
});

function createAda(): string
{
    return (string) scim('POST', '/Users', oktaUser())->assertCreated()->json('id');
}

it('provisions a new member from Okta', function (): void {
    $response = scim('POST', '/Users', oktaUser())
        ->assertCreated()
        ->assertHeader('Location')
        ->assertHeader('ETag')
        ->assertJsonPath('userName', 'ada@acme.test')
        ->assertJsonPath('name.formatted', 'Ada Lovelace')
        ->assertJsonPath('displayName', 'Ada Lovelace')
        ->assertJsonPath('externalId', '00u1okta')
        ->assertJsonPath('active', true)
        ->assertJsonPath('meta.resourceType', 'User');

    $user = User::query()->where('email', 'ada@acme.test')->sole();

    expect($this->acme->membershipFor($user)?->source->value)->toBe('scim')
        ->and(ScimUser::query()->sole()->id)->toBe($response->json('id'))
        ->and(ScimUser::query()->sole()->created_by_scim)->toBeTrue();
});

it('gets, lists and filters users the way identity providers probe', function (): void {
    $id = createAda();

    scim('GET', '/Users/'.$id)->assertOk()->assertJsonPath('id', $id);
    scim('GET', '/Users?filter='.urlencode('userName eq "ADA@acme.test"'))->assertOk()->assertJsonPath('totalResults', 1);
    scim('GET', '/Users?filter='.urlencode('externalId eq "00u1okta"'))->assertOk()->assertJsonPath('Resources.0.id', $id);
    scim('GET', '/Users?filter='.urlencode('userName eq "nobody@acme.test"'))->assertOk()->assertJsonPath('totalResults', 0)->assertJsonPath('Resources', []);
    scim('GET', '/Users/01JAAAAAAAAAAAAAAAAAAAAAAA')->assertNotFound()->assertJsonPath('status', '404');
});

it('never matches a different account through LIKE wildcards', function (): void {
    scim('POST', '/Users', oktaUser('axb@acme.test', 'x1'))->assertCreated();

    scim('GET', '/Users?filter='.urlencode('userName eq "a_b@acme.test"'))->assertOk()->assertJsonPath('totalResults', 0);
});

it('links an existing account on the organization\'s domain', function (): void {
    $ada = user(['email' => 'ada@acme.test']);

    scim('POST', '/Users', oktaUser())->assertCreated();

    expect(ScimUser::query()->sole()->user_id)->toBe($ada->getKey())
        ->and(ScimUser::query()->sole()->created_by_scim)->toBeFalse();
});

it('refuses emails outside the organization\'s domains', function (): void {
    user(['email' => 'victim@gmail.test']);

    scim('POST', '/Users', oktaUser('victim@gmail.test'))->assertStatus(400)->assertJsonPath('scimType', 'invalidValue');

    expect(ScimUser::query()->count())->toBe(0);
});

it('refuses duplicates', function (): void {
    createAda();

    scim('POST', '/Users', oktaUser())->assertStatus(409)->assertJsonPath('scimType', 'uniqueness');
});

it('deprovisions with Okta\'s PATCH and restores', function (): void {
    $id = createAda();
    $ada = User::query()->where('email', 'ada@acme.test')->sole();

    scim('PATCH', '/Users/'.$id, patchOps([['op' => 'replace', 'value' => ['active' => false]]]))->assertOk()->assertJsonPath('active', false);

    // Created by SCIM and in no other organization: deactivated, never deleted.
    expect($this->acme->membershipFor($ada))->toBeNull()
        ->and(app(Users::class)->status($ada->refresh()))->toBe(UserStatus::Deactivated)
        ->and(User::query()->whereKey($ada->getKey())->exists())->toBeTrue();

    scim('PATCH', '/Users/'.$id, patchOps([['op' => 'replace', 'value' => ['active' => true]]]))->assertOk()->assertJsonPath('active', true);

    expect($this->acme->membershipFor($ada))->not->toBeNull()
        ->and(app(Users::class)->status($ada->refresh()))->toBe(UserStatus::Active);
});

it('deprovisions with Entra\'s capitalised ops and string booleans', function (): void {
    $id = createAda();

    scim('PATCH', '/Users/'.$id, patchOps([['op' => 'Replace', 'path' => 'active', 'value' => 'False']]))->assertOk()->assertJsonPath('active', false);
});

it('keeps accounts that belong to other organizations active', function (): void {
    $ada = user(['email' => 'ada@acme.test']);
    app(AddMemberAction::class)->execute(organization(attributes: ['name' => 'Globex']), ['user' => $ada->getRouteKey()]);
    $id = (string) scim('POST', '/Users', oktaUser())->json('id');

    scim('DELETE', '/Users/'.$id)->assertNoContent();

    expect($this->acme->membershipFor($ada))->toBeNull()
        ->and(app(Users::class)->status($ada))->toBe(UserStatus::Active);

    scim('GET', '/Users/'.$id)->assertNotFound();
});

it('protects the organization\'s owner', function (): void {
    $owner = $this->acme->owner;
    $owner->update(['email' => 'boss@acme.test']);
    $id = (string) scim('POST', '/Users', oktaUser('boss@acme.test', 'boss'))->json('id');

    scim('PATCH', '/Users/'.$id, patchOps([['op' => 'replace', 'path' => 'active', 'value' => false]]))
        ->assertStatus(409)
        ->assertJsonPath('scimType', 'mutability');
});

it('replaces a user with PUT', function (): void {
    $id = createAda();

    scim('PUT', '/Users/'.$id, ['userName' => 'ada.king@acme.test', 'name' => ['formatted' => 'Ada King'], 'displayName' => 'Countess', 'active' => true, 'externalId' => '00u1okta'])
        ->assertOk()
        ->assertJsonPath('userName', 'ada.king@acme.test')
        ->assertJsonPath('name.formatted', 'Ada King')
        ->assertJsonPath('displayName', 'Countess');

    scim('PUT', '/Users/'.$id, ['userName' => 'ada@elsewhere.test'])->assertStatus(400);
});

it('patches names and emails by path', function (): void {
    $id = createAda();

    scim('PATCH', '/Users/'.$id, patchOps([
        ['op' => 'replace', 'path' => 'name.familyName', 'value' => 'King'],
        ['op' => 'replace', 'path' => 'emails[type eq "work"].value', 'value' => 'ada.k@acme.test'],
        ['op' => 'replace', 'path' => 'userName', 'value' => 'ada.k@acme.test'],
    ]))->assertOk()->assertJsonPath('name.formatted', 'Ada King')->assertJsonPath('userName', 'ada.k@acme.test');

    scim('PATCH', '/Users/'.$id, patchOps([['op' => 'replace', 'path' => 'nickName', 'value' => 'x']]))
        ->assertStatus(400)
        ->assertJsonPath('scimType', 'invalidPath');
});

it('links the SSO identity from externalId when the token names a connection', function (): void {
    $connection = SsoConnection::factory()->create(['organization_id' => $this->acme->id, 'slug' => 'acme-okta']);
    [, $this->scimToken] = [null, app(CreateScimTokenAction::class)->execute($this->acme, ['name' => 'Okta SSO', 'sso_connection' => 'acme-okta'])->plain];

    createAda();

    expect(SsoIdentity::query()->sole()->only(['connection_id', 'subject']))->toBe(['connection_id' => $connection->id, 'subject' => '00u1okta']);
});

it('writes nothing for a PATCH that only echoes the current view', function (): void {
    $id = (string) scim('POST', '/Users', ['userName' => 'grace@acme.test', 'name' => ['formatted' => 'Grace Hopper']])->json('id');
    $before = AuditEntry::query()->count();

    scim('PATCH', '/Users/'.$id, patchOps([['op' => 'replace', 'path' => 'externalId', 'value' => null]]))->assertOk();

    expect(AuditEntry::query()->count())->toBe($before)
        ->and(AuditEntry::query()->where('action', 'profile.updated')->exists())->toBeFalse();
});

it('audits SCIM changes with the token that made them', function (): void {
    createAda();

    $entry = AuditEntry::query()->where('action', 'user.created')->sole();

    expect($entry->surface)->toBe('scim')
        ->and($entry->context['scim_token']['name'])->toBe('Okta');
});
