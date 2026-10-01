<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use JayI\Roster\Actions\CreateScimTokenAction;
use JayI\Roster\Actions\RevokeScimTokenAction;
use JayI\Roster\Models\ScimToken;

beforeEach(function (): void {
    [, $this->scimToken] = scimOrg();
});

it('accepts a valid token and stamps its use', function (): void {
    scim('GET', '/Users')->assertOk()->assertHeader('Content-Type', 'application/scim+json');

    expect(ScimToken::query()->sole()->last_used_at)->not->toBeNull();
});

it('refuses missing, wrong, revoked, expired and other-organization tokens', function (string $case): void {
    $token = match ($case) {
        'missing' => '',
        'wrong' => 'scim_nope',
        'revoked' => (function (): string {
            app(RevokeScimTokenAction::class)->execute(ScimToken::query()->sole());

            return $this->scimToken;
        })(),
        'expired' => (function (): string {
            ScimToken::query()->update(['expires_at' => now()->subDay()]);

            return $this->scimToken;
        })(),
        'other organization' => app(CreateScimTokenAction::class)->execute(organization(attributes: ['name' => 'Globex']), ['name' => 'x'])->plain,
    };

    scim('GET', '/Users', token: $token)
        ->assertUnauthorized()
        ->assertJsonPath('schemas.0', 'urn:ietf:params:scim:api:messages:2.0:Error')
        ->assertJsonPath('status', '401');
})->with(['missing', 'wrong', 'revoked', 'expired', 'other organization']);
