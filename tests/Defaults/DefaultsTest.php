<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('keeps the HTTP API off until enabled', function (): void {
    expect(config('roster.routes.enabled'))->toBeFalse()
        ->and(Route::has('roster.users.index'))->toBeFalse();
});

it('keeps both MCP transports off until enabled', function (): void {
    expect(config('roster.mcp.web.enabled'))->toBeFalse()
        ->and(config('roster.mcp.local.enabled'))->toBeFalse();
});

it('registers the roster.active middleware alias', function (): void {
    expect(app('router')->getMiddleware())->toHaveKey('roster.active');
});
