<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

beforeEach(fn () => signInAsSuperAdmin());

it('shows a suspension in the audit log', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);

    visit('/atrium/roster/users/'.$ada->getRouteKey())
        ->select('#new-status', 'suspended')
        ->type('#status-reason', 'Testing')
        ->click('@change-status')
        ->assertSee('User suspended.')
        ->navigate('/atrium/roster/audit')
        ->assertSee('user.suspended')
        ->assertSee('Ada Lovelace');
});
