<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Roster\Domains\User\Actions\SuspendUserAction;

beforeEach(function (): void {
    Route::middleware(['web', 'roster.active'])->get('roster-test/dashboard', fn (): string => 'ok');
});

it('lets active users through', function (): void {
    $this->actingAs(user())->get('roster-test/dashboard')->assertOk()->assertSee('ok');
});

it('lets guests through for the auth middleware to handle', function (): void {
    $this->get('roster-test/dashboard')->assertOk();
});

it('rejects suspended users', function (): void {
    $user = app(SuspendUserAction::class)->execute(user());

    $this->actingAs($user)->get('roster-test/dashboard')->assertForbidden();
});
