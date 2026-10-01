<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use JayI\Roster\Models\Impersonation;

it('impersonates from the user page and ends from the list', function (): void {
    $admin = user(['name' => 'Admin']);
    grant($admin, 'super-admin');
    $ada = user(['name' => 'Ada']);
    $this->actingAs($admin);

    $this->get(route('atrium.roster.users.show', $ada->getRouteKey()))->assertOk()->assertSee('Impersonate');

    $redirect = $this->post(route('atrium.roster.users.impersonate', $ada->getRouteKey()), ['reason' => 'Ticket 42'])->assertRedirect();

    $this->get($redirect->headers->get('Location'));

    expect(Auth::id())->toBe($ada->getKey());

    $this->post(route('roster.impersonation.leave'));
    $this->get(route('atrium.roster.impersonations.index'))->assertOk()->assertSee('Ticket 42');

    $pending = Impersonation::factory()->create(['impersonator_id' => user()->getKey(), 'user_id' => $ada->getKey()]);

    $this->delete(route('atrium.roster.impersonations.stop', $pending->id))->assertRedirect();

    expect($pending->refresh()->end_reason)->toBe('forced');
});
