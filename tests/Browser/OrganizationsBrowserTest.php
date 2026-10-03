<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Notification;
use JayI\Roster\Domains\Invitation\Models\InvitationModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

beforeEach(fn () => signInAsSuperAdmin());

it('creates an organization, adds a team and sends an invitation', function (): void {
    Notification::fake();

    visit('/atrium/roster/organizations')
        ->click('@new-organization')
        ->type('name', 'Acme')
        ->click('@create-organization')
        ->assertSee('Organization created.')
        ->click('@tab-teams')
        ->type('name', 'Ops')
        ->click('@create-team')
        ->assertSee('Team created.')
        ->navigate('/atrium/roster/organizations/acme?tab=invitations')
        ->type('email', 'new@example.com')
        ->click('@send-invitation')
        ->assertSee('Invitation sent.')
        ->assertSee('new@example.com');

    expect(OrganizationModel::query()->sole()->teams()->count())->toBe(1)
        ->and(InvitationModel::query()->sole()->email)->toBe('new@example.com');
});

it('links an organization to an external record and finds it by account number', function (): void {
    organization(attributes: ['name' => 'Acme']);

    visit('/atrium/roster/organizations/acme?tab=settings')
        ->assertSee('Not linked to any external system.')
        ->type('#link-source', 'erp')
        ->type('#link-external-id', 'C-100')
        ->type('#link-account-number', 'A-42')
        ->click('@link-organization')
        ->assertSee('Linked.')
        ->assertPresent('@external-link');

    visit('/atrium/roster/organizations?account_number=A-42')
        ->assertSee('Acme')
        ->assertSee('C-100');
});

it('deletes an organization from the danger zone after confirming', function (): void {
    organization(attributes: ['name' => 'Doomed Co']);

    visit('/atrium/roster/organizations/doomed-co?tab=settings')
        ->assertSee('Danger zone')
        ->check('#confirm-delete-organization')
        ->click('@delete-organization')
        ->assertSee('Organization deleted.');

    expect(OrganizationModel::query()->where('slug', 'doomed-co')->exists())->toBeFalse();
});
