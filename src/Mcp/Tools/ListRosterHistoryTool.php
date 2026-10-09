<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Mcp\Tools;

use RefactorCircus\Foundation\Mcp\Tools\ListHistoryTool;

/**
 * Roster's history as the suite-wide audit log records it, once refactor-circus/keen is
 * installed. Until then it answers that the log is not installed.
 */
final class ListRosterHistoryTool extends ListHistoryTool {}
