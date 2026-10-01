<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\DeactivateUserAction;
use JayI\Roster\Actions\ReactivateUserAction;
use JayI\Roster\Actions\SuspendUserAction;
use JayI\Roster\Actions\UpdateProfileAction;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Models\Profile;

it('creates a profile lazily on first update', function (): void {
    $user = user();

    expect(Profile::query()->count())->toBe(0)
        ->and($user->rosterStatus())->toBe(UserStatus::Active);

    $updated = app(UpdateProfileAction::class)->execute($user, ['bio' => 'Mathematician', 'meta' => ['team' => 'x']]);

    expect(Profile::query()->count())->toBe(1)
        ->and($updated->rosterProfile?->bio)->toBe('Mathematician')
        ->and($updated->rosterProfile?->meta)->toBe(['team' => 'x']);
});

it('validates profile fields', function (array $data): void {
    validator($data, UpdateProfileAction::rules())->validate();
})->with([
    'bad timezone' => [['timezone' => 'Mars/Olympus']],
    'bad avatar url' => [['avatar_url' => 'not a url']],
    'meta not an object' => [['meta' => 'nope']],
])->throws(ValidationException::class);

it('suspends a user with a reason', function (): void {
    $user = app(SuspendUserAction::class)->execute(user(), ['reason' => 'Chargeback']);

    expect($user->rosterStatus())->toBe(UserStatus::Suspended)
        ->and($user->rosterProfile?->status_reason)->toBe('Chargeback')
        ->and($user->rosterProfile?->status_changed_at)->not->toBeNull();
});

it('deactivates a suspended user', function (): void {
    $user = app(SuspendUserAction::class)->execute(user());

    expect(app(DeactivateUserAction::class)->execute($user)->rosterStatus())->toBe(UserStatus::Deactivated);
});

it('reactivates a user and clears the reason', function (): void {
    $user = app(DeactivateUserAction::class)->execute(user(), ['reason' => 'Left']);

    $user = app(ReactivateUserAction::class)->execute($user);

    expect($user->rosterStatus())->toBe(UserStatus::Active)
        ->and($user->rosterProfile?->status_reason)->toBeNull();
});

it('refuses a transition to the current status', function (string $action): void {
    $user = user();

    if ($action !== ReactivateUserAction::class) {
        app($action)->execute($user);
    }

    app($action)->execute($user->refresh());
})->with([
    SuspendUserAction::class,
    DeactivateUserAction::class,
    ReactivateUserAction::class,
])->throws(ValidationException::class);

it('refuses to suspend or deactivate yourself', function (string $action): void {
    $user = user();

    app($action)->execute($user, [], $user);
})->with([
    SuspendUserAction::class,
    DeactivateUserAction::class,
])->throws(ValidationException::class);
