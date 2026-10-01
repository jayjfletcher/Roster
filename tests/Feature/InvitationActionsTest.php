<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\AcceptInvitationAction;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\CreateInvitationAction;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Actions\DeclineInvitationAction;
use JayI\Roster\Actions\ListInvitationsAction;
use JayI\Roster\Actions\RevokeInvitationAction;
use JayI\Roster\Enums\InvitationStatus;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Organization;
use JayI\Roster\Notifications\InvitationNotification;
use JayI\Roster\Roster;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * Send an invitation and return its plain token from the faked email.
 */
function invite(Organization $organization, string $email, array $teams = []): string
{
    app(CreateInvitationAction::class)->execute($organization, ['email' => $email, 'teams' => $teams]);

    $token = null;

    Notification::assertSentOnDemand(InvitationNotification::class, function (InvitationNotification $notification) use ($email, &$token): bool {
        $token = $notification->token;

        return $notification->invitation->email === strtolower($email);
    });

    return (string) $token;
}

it('emails an invitation without storing the token', function (): void {
    $token = invite(organization(), 'Ada@Example.com');

    $invitation = Invitation::query()->sole();

    expect($invitation->email)->toBe('ada@example.com')
        ->and($invitation->token_hash)->not->toBe($token)
        ->and($invitation->status())->toBe(InvitationStatus::Pending)
        ->and($invitation->toArray())->not->toHaveKey('token_hash');
});

it('accepts an invitation into the organization and its teams', function (): void {
    $organization = organization();
    app(CreateTeamAction::class)->execute($organization, ['name' => 'Ops']);
    $token = invite($organization, 'ada@example.com', ['ops']);
    $ada = user(['email' => 'ada@example.com']);

    $invitation = app(AcceptInvitationAction::class)->execute(['token' => $token], $ada);

    expect($invitation->status())->toBe(InvitationStatus::Accepted)
        ->and($organization->membershipFor($ada)?->source)->toBe(MembershipSource::Invitation)
        ->and($organization->teams()->sole()->hasMember($ada))->toBeTrue()
        ->and(app(Roster::class)->organization($ada)?->is($organization))->toBeTrue();
});

it('refuses an invitation answered by a different user', function (): void {
    $token = invite(organization(), 'ada@example.com');

    app(AcceptInvitationAction::class)->execute(['token' => $token], user(['email' => 'eve@example.com']));
})->throws(ValidationException::class);

it('refuses expired, revoked, used and unknown tokens', function (string $state): void {
    $token = invite(organization(), 'ada@example.com');
    $ada = user(['email' => 'ada@example.com']);
    $invitation = Invitation::query()->sole();

    match ($state) {
        'expired' => $invitation->update(['expires_at' => now()->subMinute()]),
        'revoked' => app(RevokeInvitationAction::class)->execute($invitation),
        'used' => app(AcceptInvitationAction::class)->execute(['token' => $token], $ada),
        'unknown' => $token = 'nope',
    };

    app(AcceptInvitationAction::class)->execute(['token' => $token], $ada);
})->with(['expired', 'revoked', 'used', 'unknown'])->throws(ValidationException::class);

it('declines an invitation', function (): void {
    $token = invite(organization(), 'ada@example.com');

    $invitation = app(DeclineInvitationAction::class)->execute(['token' => $token], user(['email' => 'ada@example.com']));

    expect($invitation->status())->toBe(InvitationStatus::Declined);
});

it('refuses to invite a member, repeat a pending invite, or use foreign teams', function (string $case): void {
    $organization = organization();

    match ($case) {
        'member' => (function () use ($organization): void {
            $ada = user(['email' => 'ada@example.com']);
            app(AddMemberAction::class)->execute($organization, ['user' => $ada->getRouteKey()]);
        })(),
        'pending' => invite($organization, 'ada@example.com'),
        'foreign team' => app(CreateTeamAction::class)->execute(organization(), ['name' => 'Ops']),
    };

    app(CreateInvitationAction::class)->execute($organization, [
        'email' => 'ADA@example.com',
        'teams' => $case === 'foreign team' ? ['ops'] : [],
    ]);
})->with(['member', 'pending', 'foreign team'])->throws(ValidationException::class);

it('lists invitations by status', function (): void {
    $organization = organization();
    invite($organization, 'ada@example.com');
    invite($organization, 'grace@example.com');
    app(RevokeInvitationAction::class)->execute(Invitation::query()->where('email', 'grace@example.com')->sole());

    $emails = fn (string $status): array => collect(app(ListInvitationsAction::class)->execute($organization, ['status' => $status])->items())->pluck('email')->all();

    expect($emails('pending'))->toBe(['ada@example.com'])
        ->and($emails('revoked'))->toBe(['grace@example.com']);
});

it('links to a custom accept url when configured', function (): void {
    config()->set('roster.invitations.accept_url', 'https://app.test/join/{token}');

    $organization = organization();
    app(CreateInvitationAction::class)->execute($organization, ['email' => 'ada@example.com']);

    Notification::assertSentOnDemand(
        InvitationNotification::class,
        fn (InvitationNotification $notification): bool => $notification->url() === 'https://app.test/join/'.$notification->token,
    );
});
