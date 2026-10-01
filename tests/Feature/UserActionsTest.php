<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\CreateUserAction;
use JayI\Roster\Actions\DeleteUserAction;
use JayI\Roster\Actions\ListUsersAction;
use JayI\Roster\Actions\ShowUserAction;
use JayI\Roster\Actions\UpdateUserAction;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Events\Action\UserCreatedActionEvent;
use JayI\Roster\Events\Action\UserCreatingActionEvent;
use JayI\Roster\Models\Profile;
use Workbench\App\Models\User;

it('creates a user with a profile', function (): void {
    $user = app(CreateUserAction::class)->execute([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'display_name' => 'Ada',
        'timezone' => 'Europe/London',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->getAttribute('email'))->toBe('ada@example.com')
        ->and(Hash::check('secret-password', (string) $user->getAttribute('password')))->toBeTrue()
        ->and($user->rosterProfile?->display_name)->toBe('Ada')
        ->and($user->rosterProfile?->timezone)->toBe('Europe/London')
        ->and($user->isRosterActive())->toBeTrue();
});

it('sets a random password when none is given', function (): void {
    $user = app(CreateUserAction::class)->execute(['name' => 'Ada', 'email' => 'ada@example.com']);

    expect($user->getAttribute('password'))->toBeString()->not->toBeEmpty();
});

it('rejects a duplicate email', function (): void {
    user(['email' => 'taken@example.com']);

    validator(['name' => 'Ada', 'email' => 'taken@example.com'], CreateUserAction::rules())->validate();
})->throws(ValidationException::class);

it('dispatches starting and finished events', function (): void {
    Event::fake([UserCreatingActionEvent::class, UserCreatedActionEvent::class]);

    app(CreateUserAction::class)->execute(['name' => 'Ada', 'email' => 'ada@example.com']);

    Event::assertDispatched(UserCreatingActionEvent::class);
    Event::assertDispatched(UserCreatedActionEvent::class);

    expect(new UserCreatingActionEvent([]))->toBeInstanceOf(ActionStartingEvent::class)
        ->and(new UserCreatedActionEvent(user()))->toBeInstanceOf(ActionFinishedEvent::class);
});

it('updates only the given fields', function (): void {
    $user = user(['name' => 'Ada', 'email' => 'ada@example.com']);

    $updated = app(UpdateUserAction::class)->execute($user, ['name' => 'Ada King']);

    expect($updated->getAttribute('name'))->toBe('Ada King')
        ->and($updated->getAttribute('email'))->toBe('ada@example.com');
});

it('lets a user keep their own email on update', function (): void {
    $user = user(['email' => 'ada@example.com']);

    $data = validator(['email' => 'ada@example.com'], UpdateUserAction::rules($user))->validate();

    expect($data)->toBe(['email' => 'ada@example.com']);
});

it('shows a user with their profile loaded', function (): void {
    $user = user();

    expect(app(ShowUserAction::class)->execute($user)->relationLoaded('rosterProfile'))->toBeTrue();
});

it('deletes a user and their profile', function (): void {
    $user = user();
    $user->roster();

    app(DeleteUserAction::class)->execute($user);

    expect(User::query()->count())->toBe(0)
        ->and(Profile::query()->count())->toBe(0);
});

it('refuses to delete yourself', function (): void {
    $user = user();

    app(DeleteUserAction::class)->execute($user, $user);
})->throws(ValidationException::class);

it('lists users with search and status filters', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);
    $grace = user(['name' => 'Grace Hopper']);
    $alan = user(['name' => 'Alan Turing']);

    Profile::factory()->suspended()->create(['user_id' => $grace->getKey(), 'display_name' => 'Grace']);
    Profile::factory()->create(['user_id' => $alan->getKey(), 'display_name' => 'Prof']);

    $list = fn (array $filters): array => collect(app(ListUsersAction::class)->execute($filters)->items())
        ->map(fn (User $user): mixed => $user->getKey())
        ->all();

    expect($list([]))->toBe([$ada->getKey(), $grace->getKey(), $alan->getKey()])
        ->and($list(['search' => 'grace']))->toBe([$grace->getKey()])
        ->and($list(['search' => 'prof']))->toBe([$alan->getKey()])
        // A user with no profile row counts as active.
        ->and($list(['status' => 'active']))->toBe([$ada->getKey(), $alan->getKey()])
        ->and($list(['status' => 'suspended']))->toBe([$grace->getKey()])
        ->and(app(ListUsersAction::class)->execute(['per_page' => 2])->total())->toBe(3);
});
