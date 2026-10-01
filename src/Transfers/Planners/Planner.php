<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Planners;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Models\Transfer;

/**
 * Decides what one imported row would do, and does it.
 *
 * The same plan() runs for the preview and again right before apply(), so a
 * confirmed import acts on the state at that moment, not at upload.
 */
abstract class Planner
{
    public const string CREATE = 'create';

    public const string LINK = 'link';

    public const string INVITE = 'invite';

    public const string UPDATE = 'update';

    public const string SKIP = 'skip';

    public const string ERROR = 'error';

    /**
     * @param  array<string, string>  $values
     * @return array{action: string, reasons: array<int, string>}
     */
    abstract public function plan(array $values, Transfer $transfer, ?Model $actor): array;

    /**
     * Apply the row; returns the action actually taken and why.
     *
     * @param  array<string, string>  $values
     * @return array{action: string, reasons: array<int, string>}
     */
    abstract public function apply(array $values, Transfer $transfer, ?Model $actor): array;

    /**
     * @return array<int, string>
     */
    protected function list(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[;,|]/', $value) ?: [])));
    }

    /**
     * @return array{action: string, reasons: array<int, string>}
     */
    protected function outcome(string $action, string ...$reasons): array
    {
        return ['action' => $action, 'reasons' => array_values($reasons)];
    }
}
