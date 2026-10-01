<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Flows\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Csv\Reader;
use JayI\Roster\Transfers\Transfers;

/**
 * Check the file and plan every row. Changes nothing but the report.
 */
final class ValidateImport
{
    /**
     * @return array{valid: bool, expires_at?: string}
     */
    public function execute(string $transfer): array
    {
        $model = Transfer::query()->findOrFail($transfer);
        $transfers = app(Transfers::class);
        $reader = new Reader($transfers->disk(), (string) $model->input_path, $model->type->columns());

        try {
            $count = $reader->validate();
        } catch (ValidationException $exception) {
            $model->update([
                'status' => TransferStatus::Failed,
                'finished_at' => now(),
                'report' => ['error' => (string) collect($exception->errors())->flatten()->first(), 'summary' => [], 'rows' => []],
            ]);

            return ['valid' => false];
        }

        $planner = $transfers->planner($model);
        $requester = $model->requester;
        $actor = $requester instanceof Model ? $requester : null;
        $rows = [];

        foreach ($reader->rows() as $row) {
            $rows[] = ['line' => $row['line'], 'values' => $row['values']] + $planner->plan($row['values'], $model, $actor);
        }

        $expires = now()->addHours((int) config('roster.transfers.confirm_within_hours', 24));

        $model->update([
            'status' => TransferStatus::AwaitingConfirmation,
            'row_count' => $count,
            'expires_at' => $expires,
            'report' => ['summary' => self::count($rows, 'action'), 'rows' => $rows],
        ]);

        return ['valid' => true, 'expires_at' => $expires->toIso8601String()];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    public static function count(array $rows, string $key): array
    {
        $counts = [];

        foreach ($rows as $row) {
            $action = (string) ($row[$key] ?? 'error');
            $counts[$action] = ($counts[$action] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }
}
