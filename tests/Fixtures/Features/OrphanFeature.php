<?php

declare(strict_types=1);

namespace JayI\Roster\Tests\Fixtures\Features;

use JayI\Missing\LayeredFeature;

/**
 * A feature whose parent class isn't installed, as RosterSupportFeature is
 * without jayi/pennantplus. Loading it throws.
 */
class OrphanFeature extends LayeredFeature {}
