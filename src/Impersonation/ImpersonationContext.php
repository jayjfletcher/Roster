<?php

declare(strict_types=1);

namespace JayI\Roster\Impersonation;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JayI\Roster\Models\Impersonation;

/**
 * The impersonation the current session is in, if any.
 *
 * Bound per request; reads the session lazily so code paths without one
 * (console, API tokens) are never affected.
 */
final class ImpersonationContext
{
    public const string SESSION_KEY = 'roster.impersonation';

    private ?Impersonation $resolved = null;

    private bool $loaded = false;

    public function __construct(
        private readonly Application $app,
        private readonly Repository $config,
    ) {}

    /**
     * The session's impersonation while it is still active.
     */
    public function active(): ?Impersonation
    {
        if (! $this->loaded) {
            $this->loaded = true;
            $id = $this->sessionId();
            $impersonation = $id === null ? null : Impersonation::query()->find($id);
            $this->resolved = $impersonation instanceof Impersonation && $impersonation->isActive() ? $impersonation : null;
        }

        return $this->resolved;
    }

    /**
     * The id stored in the session, active or not.
     */
    public function sessionId(): ?string
    {
        if (! $this->app->bound('request')) {
            return null;
        }

        $request = $this->app->make('request');

        if (! $request->hasSession()) {
            return null;
        }

        $id = $request->session()->get(self::SESSION_KEY);

        return is_string($id) ? $id : null;
    }

    public function impersonator(): ?Model
    {
        $impersonator = $this->active()?->impersonator;

        return $impersonator instanceof Model ? $impersonator : null;
    }

    /**
     * Whether a permission or ability is refused while impersonating.
     */
    public function isBlocked(string $permission): bool
    {
        if ($this->active() === null) {
            return false;
        }

        foreach ((array) $this->config->get('roster.impersonation.blocked', []) as $pattern) {
            if (is_string($pattern) && Str::is($pattern, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function forget(): void
    {
        $this->resolved = null;
        $this->loaded = false;
    }
}
