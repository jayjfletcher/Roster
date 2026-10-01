<?php

declare(strict_types=1);

use JayI\Roster\Mcp\Tools\CreateScimTokenTool;
use JayI\Roster\Mcp\Tools\ListScimTokensTool;
use JayI\Roster\Mcp\Tools\RevokeScimTokenTool;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\ScimToken;

it('issues a token once, lists without it, and revokes', function (): void {
    organization(attributes: ['name' => 'Acme']);

    $response = $this->postJson(route('roster.organizations.scim-tokens.store', 'acme'), ['name' => 'Okta', 'expires_in_days' => 30])
        ->assertCreated()
        ->assertJsonPath('data.usable', true)
        ->assertJsonPath('data.base_url', url('scim/v2/acme'));

    $token = (string) $response->json('token');

    expect($token)->toStartWith('scim_')
        ->and(ScimToken::query()->sole()->token_hash)->toBe(hash('sha256', $token));

    $this->getJson(route('roster.organizations.scim-tokens.index', 'acme'))->assertOk()->assertJsonMissingPath('token')->assertDontSee($token);

    mcpTool(ListScimTokensTool::class, ['organization' => 'acme'])->assertOk()->assertDontSee($token);
    mcpTool(CreateScimTokenTool::class, ['organization' => 'acme', 'name' => 'Entra'])->assertOk()->assertSee('scim_');

    $this->deleteJson(route('roster.scim-tokens.destroy', ScimToken::query()->where('name', 'Okta')->sole()->id))->assertOk()->assertJsonPath('data.usable', false);
    mcpTool(RevokeScimTokenTool::class, ['token' => ScimToken::query()->where('name', 'Entra')->sole()->id])->assertOk();

    expect(AuditEntry::query()->where('action', 'scim_token.created')->count())->toBe(2)
        ->and(json_encode(AuditEntry::query()->get()->toArray()))->not->toContain($token);
});
