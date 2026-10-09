<?php

declare(strict_types=1);

it('offers no MCP redirect domains without Cortex', function (): void {
    $ada = user();
    $this->actingAs($ada);
    $acme = organization($ada, ['name' => 'Acme']);

    $this->get(route('atrium.roster.organizations.show', $acme))
        ->assertOk()
        ->assertDontSee('data-testid="tab-mcp"', false);

    $this->get(route('atrium.roster.users.show', $ada->getRouteKey()))
        ->assertOk()
        ->assertDontSee('data-testid="redirect-domains-card"', false);

    $this->post(route('atrium.roster.organizations.redirect-domains.store', $acme), ['domain' => 'claude.ai'])->assertNotFound();
    $this->post(route('atrium.roster.users.redirect-domains.store', $ada->getRouteKey()), ['domain' => 'claude.ai'])->assertNotFound();
});
