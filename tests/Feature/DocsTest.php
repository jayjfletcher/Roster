<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Roster\Mcp\RosterServer;

function readme(): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
}

it('documents every API route in the README', function (): void {
    $readme = readme();

    $missing = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name): bool => str_starts_with($name, 'roster.') && ! str_starts_with($name, 'roster.invitations.page') && $name !== 'roster.invitations.show' && ! str_starts_with($name, 'roster.impersonation.') && ! str_starts_with($name, 'roster.sso.') && ! str_starts_with($name, 'roster.scim.'))
        ->reject(function (string $name) use ($readme): bool {
            // The table lists `roster.x.index`, `.store` together; match the full
            // name or its last segment after a listed sibling.
            $parts = explode('.', $name);
            $last = array_pop($parts);

            return str_contains($readme, '`'.$name.'`') || str_contains($readme, '`.'.$last.'`') && str_contains($readme, '`'.implode('.', $parts).'.');
        })
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

it('documents every MCP tool in the README', function (): void {
    $readme = readme();

    $missing = collect(RosterServer::TOOLS)
        ->map(fn (string $tool): string => app($tool)->name())
        ->reject(fn (string $name): bool => str_contains($readme, '`'.$name.'`'))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});
