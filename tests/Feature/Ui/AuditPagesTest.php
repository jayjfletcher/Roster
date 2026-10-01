<?php

declare(strict_types=1);

use JayI\Roster\Actions\SuspendUserAction;
use JayI\Roster\Models\AuditEntry;

beforeEach(function (): void {
    $this->actingAs(user(['name' => 'Admin']));
});

it('lists, filters and shows entries', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);
    app(SuspendUserAction::class)->execute($ada, ['reason' => 'Spam']);
    $entry = AuditEntry::query()->where('action', 'user.suspended')->sole();

    $this->get(route('atrium.roster.audit.index'))->assertOk()->assertSee('user.suspended')->assertSee('Ada Lovelace');
    $this->get(route('atrium.roster.audit.index', ['action' => 'user.']))->assertOk()->assertSee('user.suspended');
    $this->get(route('atrium.roster.audit.index', ['action' => 'role.']))->assertOk()->assertDontSee('user.suspended');

    $this->get(route('atrium.roster.audit.show', $entry->id))->assertOk()->assertSee('profile.status')->assertSee('suspended')->assertSee($entry->hash);
});

it('records a note', function (): void {
    $this->post(route('atrium.roster.audit.store'), ['action' => 'contract.signed', 'subject_label' => 'MSA', 'context' => ['note' => 'Signed on paper']])
        ->assertRedirect(route('atrium.roster.audit.index'));

    $entry = AuditEntry::query()->where('source', 'app')->sole();

    expect($entry->context)->toBe(['note' => 'Signed on paper'])
        ->and($entry->surface)->toBe('atrium');
});

it('shows activity on user and organization pages', function (): void {
    $ada = user(['name' => 'Ada']);
    app(SuspendUserAction::class)->execute($ada);
    organization(attributes: ['name' => 'Acme']);

    $this->get(route('atrium.roster.users.show', $ada->getRouteKey()))->assertOk()->assertSee('user.suspended');
    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'activity']))->assertOk()->assertSee('organization.created');
});
