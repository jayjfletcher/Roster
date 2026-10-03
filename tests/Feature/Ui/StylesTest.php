<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Atrium ships one precompiled stylesheet built from its own views, so a
 * Tailwind class only Roster uses silently does nothing. Every class in
 * Roster's Atrium screens must exist there or in resources/css/atrium.css.
 */
it('styles every class used on the atrium screens', function (): void {
    $root = dirname(__DIR__, 3);
    $stylesheets = file_get_contents($root.'/vendor/jayi/atrium/public/atrium.css')
        .file_get_contents($root.'/resources/css/atrium.css');

    $missing = [];

    foreach ((new Finder)->files()->in($root.'/resources/views/ui')->name('*.blade.php') as $file) {
        preg_match_all('/\b(?:class|wrapper)="([^"]*)"|@class\(\[(.*?)\]\)/s', $file->getContents(), $matches);

        foreach (array_merge($matches[1], $matches[2]) as $value) {
            // Blade expressions and @class conditions aren't class names.
            $value = (string) preg_replace('/\{\{.*?\}\}|=>.*?(,|$)/s', ' ', $value);

            foreach (preg_split('/[\s\'",]+/', $value, flags: PREG_SPLIT_NO_EMPTY) ?: [] as $class) {
                if (! preg_match('/^[a-z][a-z0-9:\-\/.\[\]%]*$/', $class)) {
                    continue;
                }

                $selector = '.'.preg_replace('/([:\/.\[\]%])/', '\\\\$1', $class);

                if (! str_contains($stylesheets, $selector)) {
                    $missing[$class] = $file->getRelativePathname();
                }
            }
        }
    }

    expect($missing)->toBe([]);
});

it('adds its styles to the dashboard head through atrium', function (): void {
    $this->actingAs(user());

    $html = $this->get(route('atrium.roster.users.index'))->assertOk()->getContent();

    expect(substr($html, 0, (int) strpos($html, '</head>')))->toContain('.roster-actions {');
});
