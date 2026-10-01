<?php

declare(strict_types=1);

namespace JayI\Roster\Tests;

/**
 * Roster booted with the audit log turned off.
 */
abstract class AuditOffTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('roster.audit.enabled', false);
    }
}
