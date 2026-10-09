<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services;

use Closure;

/**
 * What doesn't change while one import runs - its organization, teams,
 * roles, domains, the importer and their permissions - kept per transfer.
 *
 * Impex applies each row as its own job, and Laravel clears scoped services
 * between jobs, so this lives for the worker: it holds the last few
 * transfers and drops one when it finishes. Data read here can be as old as
 * the import run, which is fine for a bulk job.
 */
final class PlanCache
{
    private const int TRANSFERS = 10;

    /** @var array<string, array<string, mixed>> */
    private array $entries = [];

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $resolve
     * @return TValue
     */
    public function remember(string $transfer, string $key, Closure $resolve): mixed
    {
        if (! isset($this->entries[$transfer])) {
            // Bounded: forget the oldest transfer first.
            if (count($this->entries) >= self::TRANSFERS) {
                unset($this->entries[array_key_first($this->entries)]);
            }

            $this->entries[$transfer] = [];
        }

        if (! array_key_exists($key, $this->entries[$transfer])) {
            $this->entries[$transfer][$key] = $resolve();
        }

        return $this->entries[$transfer][$key];
    }

    public function forget(string $transfer): void
    {
        unset($this->entries[$transfer]);
    }
}
