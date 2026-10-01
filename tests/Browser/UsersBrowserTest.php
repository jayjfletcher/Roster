<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use JayI\Roster\Enums\UserStatus;
use Workbench\App\Models\User;

beforeEach(fn () => signInAsSuperAdmin());

it('lists and searches users', function (): void {
    user(['name' => 'Grace Hopper']);
    user(['name' => 'Alan Turing']);

    visit('/atrium/roster/users')
        ->assertSee('Grace Hopper')
        ->assertSee('Alan Turing')
        ->type('search', 'grace')
        ->click('@filter-users')
        ->assertSee('Grace Hopper')
        ->assertDontSee('Alan Turing');
});

it('creates a user and suspends them', function (): void {
    visit('/atrium/roster/users')
        ->click('@new-user')
        ->type('name', 'Ada Lovelace')
        ->type('email', 'ada@example.com')
        ->click('@create-user')
        ->assertSee('User created.')
        ->type('#suspend-reason', 'Testing')
        ->click('@suspend-user')
        ->assertSee('User suspended.')
        ->assertSee('Testing');

    expect(User::query()->where('email', 'ada@example.com')->sole()->rosterStatus())->toBe(UserStatus::Suspended);
});
