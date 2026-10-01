<?php

declare(strict_types=1);

use JayI\Roster\Models\ScimToken;

it('issues, shows once and revokes tokens from the organization page', function (): void {
    $this->actingAs(user());
    organization(attributes: ['name' => 'Acme']);

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'scim']))->assertOk()->assertSee(url('scim/v2/acme'));

    $this->post(route('atrium.roster.scim-tokens.store', 'acme'), ['name' => 'Okta'])->assertRedirect();

    $page = $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'scim']))->assertOk()->assertSee('scim_');
    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'scim']))->assertOk()->assertDontSee('data-testid="scim-token"', false);

    $this->delete(route('atrium.roster.scim-tokens.revoke', ScimToken::query()->sole()->id))->assertRedirect();

    expect(ScimToken::query()->sole()->revoked_at)->not->toBeNull();
});
