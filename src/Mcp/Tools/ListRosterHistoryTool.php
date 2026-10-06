<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use JayI\Foundation\Mcp\Tools\ListHistoryTool;

/**
 * Roster's history as the shared audit log records it, once jayi/keen is
 * installed. Until then it answers that the log is not installed; Roster's
 * own log stays behind list-audit-entries-tool.
 */
final class ListRosterHistoryTool extends ListHistoryTool {}
