<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Features;

use RefactorCircus\PennantPlus\Domains\Feature\Support\OnLayeredFeature;

/**
 * Switches Roster in Atrium on and off: its navigation, widgets, search and
 * pages. On until its global value is set. The `SupportFeature` suffix
 * matches PennantPlus's default `gate.global_only` pattern, so only the
 * global value counts and per-user access stays with Roster's permissions.
 *
 * Needs refactor-circus/pennantplus. Point `roster.atrium.features` at a subclass to
 * change the default, or at your own feature instead.
 */
class RosterSupportFeature extends OnLayeredFeature {}
