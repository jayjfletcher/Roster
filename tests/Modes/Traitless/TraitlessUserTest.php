<?php

declare(strict_types=1);

use JayI\Roster\Actions\SuspendUserAction;
use JayI\Roster\Actions\UpdateProfileAction;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Models\Profile;
use JayI\Roster\Support\Users;
use JayI\Roster\Tests\Fixtures\PlainUser;

it('gives a model without the trait a profile relation', function (): void {
    $user = PlainUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'x']);

    app(UpdateProfileAction::class)->execute($user, ['display_name' => 'Countess']);
    app(SuspendUserAction::class)->execute($user);

    $fresh = PlainUser::query()->with('rosterProfile')->sole();

    expect($fresh->getRelation('rosterProfile'))->toBeInstanceOf(Profile::class)
        ->and($fresh->getRelation('rosterProfile')->display_name)->toBe('Countess')
        ->and(app(Users::class)->status($fresh))->toBe(UserStatus::Suspended);
});

it('serves the HTTP API for a model without the trait', function (): void {
    PlainUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'x']);

    $this->getJson(route('roster.users.index'))->assertOk()->assertJsonPath('data.0.name', 'Ada');
});
