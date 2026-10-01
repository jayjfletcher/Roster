<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

beforeEach(fn () => signInAsSuperAdmin());

it('shows a suspension in the audit log', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);

    visit('/atrium/roster/users/'.$ada->getRouteKey())
        ->type('#suspend-reason', 'Testing')
        ->click('@suspend-user')
        ->assertSee('User suspended.')
        ->navigate('/atrium/roster/audit')
        ->assertSee('user.suspended')
        ->assertSee('Ada Lovelace');
});
