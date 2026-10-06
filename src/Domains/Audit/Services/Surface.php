<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JayI\Foundation\Support\Surface as SharedSurface;

/**
 * Which surface the current change came through: `http`, `mcp`, `cortex`,
 * `atrium`, `scim`, `web`, `cli` or `code`.
 *
 * Reads jayi/foundation's surface, so calls the shared MCP request base and
 * Cortex mark are recorded as theirs. AuditServiceProvider names the routes
 * Roster serves outside its JSON API (`scim`, `web`). Stays until the audit
 * log moves to jayi/keen.
 */
final readonly class Surface
{
    public function __construct(private SharedSurface $surface) {}

    /**
     * Mark everything recorded inside the callback as coming from `$surface`.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function using(string $surface, callable $callback): mixed
    {
        return $this->surface->using($surface, $callback);
    }

    public function current(): string
    {
        return $this->surface->current();
    }

    /**
     * The signed-in user, from the default guard - also outside HTTP, e.g.
     * code running for a queued job that authenticated with `Auth::login()`.
     */
    public function actor(): ?Model
    {
        return $this->surface->actor();
    }

    public function request(): ?Request
    {
        return $this->surface->request();
    }
}
