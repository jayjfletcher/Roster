<?php

declare(strict_types=1);

use JayI\Roster\Atrium\RosterPlugin;
use JayI\Roster\Enums\UserStatus;
use Workbench\App\Models\User;

beforeEach(function (): void {
    $this->admin = user(['name' => 'Admin']);
    $this->actingAs($this->admin);
});

it('lists and filters users', function (): void {
    $grace = user(['name' => 'Grace Hopper']);
    $grace->roster()->update(['status' => UserStatus::Suspended]);

    $this->get(route('atrium.roster.users.index'))->assertOk()->assertSee('Grace Hopper')->assertSee('Admin');

    $this->get(route('atrium.roster.users.index', ['status' => 'suspended']))
        ->assertOk()
        ->assertSee('Grace Hopper')
        ->assertDontSee('>Admin', false);
});

it('creates a user from the dashboard', function (): void {
    $this->get(route('atrium.roster.users.create'))->assertOk();

    $this->post(route('atrium.roster.users.store'), [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => '',
        'display_name' => 'Countess',
    ])->assertRedirect();

    expect(User::query()->where('email', 'ada@example.com')->sole()->roster()->display_name)->toBe('Countess');
});

it('edits a user and their profile', function (): void {
    $user = user(['name' => 'Ada']);
    $password = $user->getAttribute('password');

    $this->get(route('atrium.roster.users.show', $user->getRouteKey()))->assertOk()->assertSee('Ada');

    $this->patch(route('atrium.roster.users.update', $user->getRouteKey()), [
        'name' => 'Ada King',
        'email' => $user->getAttribute('email'),
        'password' => '',
    ])->assertRedirect(route('atrium.roster.users.show', $user->getRouteKey()));

    $this->patch(route('atrium.roster.users.profile', $user->getRouteKey()), ['bio' => 'Analyst'])->assertRedirect();

    $user->refresh();

    expect($user->getAttribute('name'))->toBe('Ada King')
        ->and($user->getAttribute('password'))->toBe($password)
        ->and($user->roster()->bio)->toBe('Analyst');
});

it('changes status from the dashboard', function (): void {
    $user = user();
    $key = $user->getRouteKey();

    $this->post(route('atrium.roster.users.suspend', $key), ['reason' => 'Spam'])->assertRedirect();
    expect($user->rosterStatus())->toBe(UserStatus::Suspended);

    $this->get(route('atrium.roster.users.show', $key))->assertSee('Spam');

    $this->post(route('atrium.roster.users.deactivate', $key))->assertRedirect();
    expect($user->refresh()->rosterStatus())->toBe(UserStatus::Deactivated);

    $this->post(route('atrium.roster.users.reactivate', $key))->assertRedirect();
    expect($user->refresh()->rosterStatus())->toBe(UserStatus::Active);
});

it('shows domain guards as form errors', function (): void {
    $this->from(route('atrium.roster.users.show', $this->admin->getRouteKey()))
        ->post(route('atrium.roster.users.suspend', $this->admin->getRouteKey()))
        ->assertSessionHasErrors('user');
});

it('deletes a user from the dashboard only once confirmed', function (): void {
    $user = user();

    $this->get(route('atrium.roster.users.show', $user->getRouteKey()))->assertOk()->assertSee('data-testid="danger-zone"', false)->assertSee('Moves the user to Deleted');

    $this->delete(route('atrium.roster.users.destroy', $user->getRouteKey()))->assertSessionHasErrors('confirm');
    expect(User::query()->whereKey($user->getKey())->exists())->toBeTrue();

    $this->delete(route('atrium.roster.users.destroy', $user->getRouteKey()), ['confirm' => '1'])
        ->assertRedirect(route('atrium.roster.users.index', ['trashed' => 'only']));

    expect(User::query()->whereKey($user->getKey())->exists())->toBeFalse();
});

it('changes status from one form, offering only the moves that apply', function (): void {
    $user = user();
    $key = $user->getRouteKey();

    $this->get(route('atrium.roster.users.show', $key))
        ->assertOk()
        ->assertSee('Change status to')
        ->assertSee('<option value="suspended"', false)
        ->assertDontSee('<option value="active"', false);

    $this->post(route('atrium.roster.users.status', $key), ['status' => 'suspended', 'reason' => 'Chargeback'])->assertRedirect();
    expect($user->fresh()->rosterStatus())->toBe(UserStatus::Suspended)
        ->and($user->fresh()->rosterProfile->status_reason)->toBe('Chargeback');

    $this->post(route('atrium.roster.users.status', $key), ['status' => 'deactivated'])->assertRedirect();
    expect($user->fresh()->rosterStatus())->toBe(UserStatus::Deactivated);

    $this->post(route('atrium.roster.users.status', $key), ['status' => 'active'])->assertRedirect();
    expect($user->fresh()->rosterStatus())->toBe(UserStatus::Active);

    $this->post(route('atrium.roster.users.status', $key), ['status' => 'pending'])->assertSessionHasErrors('status');
});

it('renders the status widget', function (): void {
    user()->roster()->update(['status' => UserStatus::Deactivated]);

    $widget = collect(app(RosterPlugin::class)->widgets())->firstOrFail(fn ($definition): bool => $definition->key === 'roster.user-status');

    $html = view('roster::ui.widgets.user-status', $widget->resolveData())->render();

    expect($html)->toContain('Deactivated');
});
