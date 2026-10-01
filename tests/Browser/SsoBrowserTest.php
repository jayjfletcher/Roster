<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

beforeEach(fn () => signInAsSuperAdmin());

it('creates an Entra ID connection and shows its callback URL', function (): void {
    organization(attributes: ['name' => 'Acme']);

    visit('/atrium/roster/organizations/acme?tab=sso')
        ->type('name', 'Entra')
        ->select('protocol', 'azure')
        ->type('tenant', 'acme.onmicrosoft.com')
        ->type('client_id', 'app')
        ->type('client_secret', 'secret')
        ->click('@create-sso')
        ->assertSee('SSO connection created.')
        ->assertSee('/roster/sso/acme-entra/callback');
});
