<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use JayI\Roster\Actions\CreateUserAction;
use JayI\Roster\Models\User;
use JayI\Roster\Support\Users;

beforeEach(function (): void {
    config()->set('roster.users.model', User::class);
});

it('manages users through the roster-owned model', function (): void {
    $user = app(CreateUserAction::class)->execute(['name' => 'Ada', 'email' => 'ada@example.com']);

    expect($user)->toBeInstanceOf(User::class)
        ->and(app(Users::class)->model())->toBe(User::class)
        ->and($user->roster()->exists)->toBeTrue()
        ->and($user->isRosterActive())->toBeTrue();
});

it('publishes the users migration only under its own tag', function (): void {
    $published = fn (string $tag): array => array_map(
        'basename',
        array_keys(ServiceProvider::pathsToPublish(null, $tag)),
    );

    expect($published('roster-users-migration'))->toBe(['users'])
        ->and($published('roster'))->not->toContain('users')
        ->and(Artisan::all())->toHaveKey('vendor:publish');
});
