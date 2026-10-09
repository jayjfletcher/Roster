<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests\Fixtures\Features;

use RefactorCircus\Missing\LayeredFeature;

/**
 * A feature whose parent class isn't installed, as RosterSupportFeature is
 * without refactor-circus/pennantplus. Loading it throws.
 */
class OrphanFeature extends LayeredFeature {}
