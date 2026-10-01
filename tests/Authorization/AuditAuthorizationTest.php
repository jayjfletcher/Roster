<?php

declare(strict_types=1);

use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\SuspendUserAction;
use JayI\Roster\Facades\Roster;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\RoleAssignment;

it('lets global viewers read everything, including IPs', function (): void {
    $viewer = user();
    RoleAssignment::query()->create(['role_id' => roleWith(['roster.audit.view'])->id, 'user_id' => $viewer->getKey()]);
    organization(attributes: ['name' => 'Acme']);

    $this->actingAs($viewer)
        ->getJson(route('roster.audit.index'))
        ->assertOk()
        ->assertJsonStructure(['data' => [['ip', 'user_agent']]]);
});

it('lets organization admins read only their organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    organization(attributes: ['name' => 'Globex']);
    $admin = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $admin->getRouteKey()]);
    grant($admin, 'admin', $acme);
    $this->actingAs($admin);

    $this->getJson(route('roster.audit.index', ['organization' => 'acme']))
        ->assertOk()
        ->assertJsonMissingPath('data.0.ip');
    $this->getJson(route('roster.audit.index', ['organization' => 'globex']))->assertForbidden();
    $this->getJson(route('roster.audit.index'))->assertForbidden();

    $globexEntry = AuditEntry::query()->where('action', 'organization.created')->where('subject_label', 'Globex')->sole();
    $acmeEntry = AuditEntry::query()->where('action', 'organization.created')->where('subject_label', 'Acme')->sole();

    $this->getJson(route('roster.audit.show', $acmeEntry->id))->assertOk();
    $this->getJson(route('roster.audit.show', $globexEntry->id))->assertForbidden();
});

it('lets users read entries about themselves', function (): void {
    $ada = user();
    app(SuspendUserAction::class)->execute($ada);
    $entry = AuditEntry::query()->where('action', 'user.suspended')->sole();
    $this->actingAs($ada);

    $this->getJson(route('roster.audit.index', ['user' => $ada->getRouteKey()]))->assertOk()->assertJsonPath('meta.total', 1);
    $this->getJson(route('roster.audit.show', $entry->id))->assertOk();
    $this->getJson(route('roster.audit.index', ['user' => user()->getRouteKey()]))->assertForbidden();
});

it('needs roster.audit.record to record, scoped to the organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $admin = user();
    app(AddMemberAction::class)->execute($acme, ['user' => $admin->getRouteKey()]);
    grant($admin, 'admin', $acme);
    $this->actingAs($admin);

    $this->postJson(route('roster.audit.store'), ['action' => 'contract.signed', 'organization' => 'acme'])->assertCreated();
    $this->postJson(route('roster.audit.store'), ['action' => 'contract.signed'])->assertForbidden();

    // Code is trusted: the fluent API needs no permission.
    expect(Roster::audit('contract.signed')->record()->exists)->toBeTrue();
});
