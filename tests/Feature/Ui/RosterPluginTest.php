<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Atrium\Navigation\NavItem;
use JayI\Atrium\Plugins\PluginRegistry;
use JayI\Atrium\Widgets\WidgetDefinition;
use JayI\Atrium\Widgets\WidgetRegistry;
use JayI\Roster\Atrium\Badges;
use JayI\Roster\Atrium\RosterPlugin;
use JayI\Roster\Enums\UserStatus;

it('registers itself with atrium', function (): void {
    expect(app(PluginRegistry::class)->has('roster'))->toBeTrue();
});

it('contributes users and organizations navigation', function (): void {
    $labels = array_map(fn (NavItem $item): string => $item->label, app(RosterPlugin::class)->navigation());

    expect($labels)->toBe(['Users', 'Organizations', 'Roles', 'Permissions', 'Impersonations', 'Imports & exports', 'Audit log']);
});

it('registers its routes inside the atrium group', function (): void {
    expect(Route::has('atrium.roster.users.index'))->toBeTrue()
        ->and(route('atrium.roster.users.index'))->toContain('/atrium/roster/users');
});

it('offers its widgets without placing them', function (): void {
    $keys = array_map(fn (WidgetDefinition $definition): string => $definition->key, app(RosterPlugin::class)->widgets());

    expect($keys)->toBe(['roster.user-status', 'roster.organizations'])
        ->and(app(WidgetRegistry::class)->all())->toHaveKeys($keys);
});

it('maps every status to a badge variant', function (): void {
    foreach (UserStatus::cases() as $status) {
        expect(Badges::forStatus($status))->toBeIn(['success', 'warning', 'neutral']);
    }
});

it('finds users from atrium search', function (): void {
    user(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    $results = app(RosterPlugin::class)->search()?->results('ada');

    expect($results)->toHaveCount(1)
        ->and($results[0]->title)->toBe('Ada Lovelace');
});
