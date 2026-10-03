<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Which surface the current change came through: `http`, `mcp`, `atrium`,
 * `web`, `cli` or `code`.
 */
final class Surface
{
    private ?string $forced = null;

    public function __construct(private readonly Application $app) {}

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
        $previous = $this->forced;
        $this->forced = $surface;

        try {
            return $callback();
        } finally {
            $this->forced = $previous;
        }
    }

    public function current(): string
    {
        if ($this->forced !== null) {
            return $this->forced;
        }

        $name = $this->request()?->route()?->getName();

        return match (true) {
            is_string($name) && str_starts_with($name, 'atrium.') => 'atrium',
            is_string($name) && str_starts_with($name, 'roster.scim.') => 'scim',
            is_string($name) && (str_starts_with($name, 'roster.invitations.page') || $name === 'roster.invitations.show' || str_starts_with($name, 'roster.impersonation.')) => 'web',
            is_string($name) && str_starts_with($name, 'roster.') => 'http',
            $this->app->runningInConsole() => 'cli',
            default => 'code',
        };
    }

    /**
     * The signed-in user, from the default guard - also outside HTTP, e.g.
     * code running for a queued job that authenticated with `Auth::login()`.
     */
    public function actor(): ?Model
    {
        $user = $this->app->make('auth')->user();

        return $user instanceof Model ? $user : null;
    }

    public function request(): ?Request
    {
        $request = $this->app->bound('request') ? $this->app->make('request') : null;

        return $request instanceof Request ? $request : null;
    }
}
