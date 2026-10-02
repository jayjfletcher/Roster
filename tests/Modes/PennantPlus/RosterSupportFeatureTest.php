<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use JayI\Atrium\Navigation\NavigationRegistry;
use JayI\Atrium\Navigation\NavItem;
use JayI\Roster\Atrium\RosterPlugin;
use JayI\Roster\Features\RosterSupportFeature;
use Laravel\Pennant\Feature;

/**
 * @return array<int, string>
 */
function navigationFor(?Authenticatable $user = null): array
{
    $request = Request::create('/atrium');
    $request->setUserResolver(fn () => $user);

    return array_map(fn (NavItem $item): string => $item->label, app(NavigationRegistry::class)->items($request));
}

/**
 * Off until its global value is set, to show the class can be overridden.
 */
class OffRosterSupportFeature extends RosterSupportFeature
{
    protected function default(): bool
    {
        return false;
    }
}

it('gates roster on the bundled feature by default', function (): void {
    expect(app(RosterPlugin::class)->features())->toBe([RosterSupportFeature::class]);
});

it('shows roster until the feature is turned off globally', function (): void {
    $user = user();

    expect(navigationFor($user))->toContain('Users');

    $this->actingAs($user)->get(route('atrium.roster.users.index'))->assertOk();

    Feature::for(null)->deactivate(RosterSupportFeature::class);

    expect(navigationFor($user))->not->toContain('Users');

    $this->actingAs($user)->get(route('atrium.roster.users.index'))->assertNotFound();
});

it('only counts the global value, leaving per-user access to permissions', function (): void {
    $user = user();

    Feature::for($user)->deactivate(RosterSupportFeature::class);

    expect(navigationFor($user))->toContain('Users');
});

it('uses a subclass named in the config instead', function (): void {
    config()->set('roster.atrium.features', [OffRosterSupportFeature::class]);

    expect(navigationFor(user()))->not->toContain('Users');

    Feature::for(null)->activate(OffRosterSupportFeature::class);

    expect(navigationFor(user()))->toContain('Users');
});
