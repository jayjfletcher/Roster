<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use JayI\Atrium\Domains\Navigation\Data\NavItem;
use JayI\Atrium\Domains\Navigation\Services\NavigationRegistry;
use JayI\Roster\Atrium\Features\RosterSupportFeature;
use JayI\Roster\Atrium\RosterPlugin;
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

it('keeps the stored name it had before it moved', function (): void {
    // Values stored before the class moved from JayI\Roster\Features.
    Feature::for(null)->deactivate('JayI\\Roster\\Features\\RosterSupportFeature');

    expect(Feature::for(null)->active(RosterSupportFeature::class))->toBeFalse()
        ->and(navigationFor(user()))->not->toContain('Users');

    Feature::define(OffRosterSupportFeature::class);

    expect(Feature::defined())->toContain('JayI\\Roster\\Features\\RosterSupportFeature', OffRosterSupportFeature::class);
});
