<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Notification;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Organization;

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

    expect(Organization::query()->sole()->teams()->count())->toBe(1)
        ->and(Invitation::query()->sole()->email)->toBe('new@example.com');
});
