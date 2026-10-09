<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Roster\Atrium\RosterPlugin;

it('links its own audit log from its sidebar group', function (): void {
    $urls = array_map(fn (NavItem $item): ?string => $item->resolveUrl(), app(RosterPlugin::class)->navigation());

    expect($urls)->toContain(route('atrium.history.show', 'roster'));
});
