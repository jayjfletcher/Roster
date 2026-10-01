<?php

declare(strict_types=1);

use JayI\Roster\Models\SsoConnection;

it('creates, edits and deletes a connection from the organization page', function (): void {
    $this->actingAs(user());
    organization(attributes: ['name' => 'Acme']);

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'sso']))->assertOk()->assertSee('Microsoft Entra ID');

    $this->post(route('atrium.roster.sso.store', 'acme'), [
        'name' => 'Okta', 'protocol' => 'oidc', 'issuer' => 'https://idp.test', 'client_id' => 'a', 'client_secret' => 'b', 'jit' => '1', 'enabled' => '1',
    ])->assertRedirect();

    $connection = SsoConnection::query()->sole();

    $this->get(route('atrium.roster.sso.show', $connection->slug))->assertOk()->assertSee(route('roster.sso.callback', $connection->slug));

    $this->patch(route('atrium.roster.sso.update', $connection->slug), ['name' => 'Okta prod', 'client_secret' => '', 'enabled' => '1'])->assertRedirect();

    expect($connection->refresh()->name)->toBe('Okta prod')
        ->and($connection->jit)->toBeFalse()
        ->and($connection->setting('client_secret'))->toBe('b');

    $this->delete(route('atrium.roster.sso.destroy', $connection->slug))->assertRedirect();
    expect(SsoConnection::query()->count())->toBe(0);
});
