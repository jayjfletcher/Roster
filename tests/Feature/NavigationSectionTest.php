<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Support\Icons;
use RefactorCircus\Roster\Atrium\RosterPlugin;

it('gives its sidebar section its own icon', function (): void {
    [$group] = app(RosterPlugin::class)->navigationGroups();

    expect($group->icon)->toBe(Icons::svg('user-group'))
        ->and($group->sort)->toBe(10);
});

it('collects its pages in that section', function (): void {
    $plugin = app(RosterPlugin::class);
    [$group] = $plugin->navigationGroups();

    $labels = array_values(array_unique(array_map(fn ($item): ?string => $item->group, $plugin->navigation())));

    expect($labels)->toBe([$group->name]);
});
