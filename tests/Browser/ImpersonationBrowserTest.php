<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

beforeEach(fn () => signInAsSuperAdmin());

it('impersonates a user and returns', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);
    grant($ada, 'super-admin');

    visit('/atrium/roster/users/'.$ada->getRouteKey())
        ->type('#impersonation-reason', 'Ticket 42')
        ->click('@impersonate-user')
        ->navigate('/atrium/roster/users')
        ->assertSee('You are acting as Ada Lovelace')
        ->click('@leave-impersonation')
        ->assertSee('You are back in your own account.');
});
