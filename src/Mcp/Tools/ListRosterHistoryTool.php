<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use JayI\Foundation\Mcp\Tools\ListHistoryTool;

/**
 * Roster's history as the suite-wide audit log records it, once jayi/keen is
 * installed. Until then it answers that the log is not installed.
 */
final class ListRosterHistoryTool extends ListHistoryTool {}
