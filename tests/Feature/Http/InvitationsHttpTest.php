<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use JayI\Roster\Notifications\InvitationNotification;

beforeEach(function (): void {
    Notification::fake();
});

function sentToken(): string
{
    $token = '';

    Notification::assertSentOnDemand(InvitationNotification::class, function (InvitationNotification $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    return $token;
}

it('invites, lists and revokes', function (): void {
    $organization = organization();

    $id = $this->postJson(route('roster.organizations.invitations.store', $organization->slug), ['email' => 'ada@example.com'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonMissingPath('data.token')
        ->assertJsonMissingPath('data.token_hash')
        ->json('data.id');

    $this->getJson(route('roster.organizations.invitations.index', $organization->slug))->assertOk()->assertJsonPath('meta.total', 1);

    $this->deleteJson(route('roster.organizations.invitations.revoke', [$organization->slug, $id]))
        ->assertOk()
        ->assertJsonPath('data.status', 'revoked');
});

it('accepts as the authenticated user', function (): void {
    $organization = organization();
    $this->postJson(route('roster.organizations.invitations.store', $organization->slug), ['email' => 'ada@example.com']);
    $token = sentToken();

    $this->postJson(route('roster.invitations.accept', $token))->assertUnauthorized();

    $this->actingAs(user(['email' => 'ada@example.com']))
        ->postJson(route('roster.invitations.accept', $token))
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted');
});

it('declines as the authenticated user and rejects other users', function (): void {
    $organization = organization();
    $this->postJson(route('roster.organizations.invitations.store', $organization->slug), ['email' => 'ada@example.com']);
    $token = sentToken();

    $this->actingAs(user(['email' => 'eve@example.com']))
        ->postJson(route('roster.invitations.decline', $token))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');

    $this->actingAs(user(['email' => 'ada@example.com']))
        ->postJson(route('roster.invitations.decline', $token))
        ->assertOk()
        ->assertJsonPath('data.status', 'declined');
});
