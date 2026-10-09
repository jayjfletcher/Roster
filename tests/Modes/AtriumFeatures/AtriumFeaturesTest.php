<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Navigation\Services\NavigationRegistry;
use RefactorCircus\Atrium\Domains\Plugins\Services\PluginRegistry;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\RosterPlugin;
use RefactorCircus\Roster\Tests\Fixtures\Features\OrphanFeature;

/**
 * @return array<int, string>
 */
function rosterNavigation(): array
{
    return array_map(
        fn (NavItem $item): string => $item->label,
        app(NavigationRegistry::class)->items(Request::create('/atrium')),
    );
}

it('names the configured features', function (): void {
    expect(app(RosterPlugin::class)->features())->toBe(['roster']);
});

it('shows roster while its feature is on', function (): void {
    Atrium::resolveFeaturesUsing(fn (string $feature): bool => true);

    expect(app(PluginRegistry::class)->authorized(Request::create('/atrium')))->toHaveKey('roster')
        ->and(rosterNavigation())->toContain('Users');

    $this->actingAs(user())->get(route('atrium.roster.users.index'))->assertOk();
});

it('hides roster and its pages while its feature is off', function (): void {
    Atrium::resolveFeaturesUsing(fn (string $feature): bool => $feature !== 'roster');

    expect(app(PluginRegistry::class)->authorized(Request::create('/atrium')))->not->toHaveKey('roster')
        ->and(rosterNavigation())->not->toContain('Users');

    $this->actingAs(user())->get(route('atrium.roster.users.index'))->assertNotFound();
});

it('skips feature classes that are not installed', function (): void {
    config()->set('roster.atrium.features', ['App\Features\Missing', 'roster']);

    expect(app(RosterPlugin::class)->features())->toBe(['roster']);
});

it('skips a feature class whose parent is not installed', function (): void {
    config()->set('roster.atrium.features', [OrphanFeature::class, 'roster']);

    expect(app(RosterPlugin::class)->features())->toBe(['roster']);
});
