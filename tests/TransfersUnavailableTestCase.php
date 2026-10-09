<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests;

use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;

/**
 * Roster as installed without the optional refactor-circus/impex package.
 */
abstract class TransfersUnavailableTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->bind(Transfers::class, fn () => new class extends Transfers
        {
            public function available(): bool
            {
                return false;
            }
        });
    }
}
