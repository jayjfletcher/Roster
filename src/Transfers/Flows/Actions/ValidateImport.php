<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Transfers\Flows\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferRowModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Domains\Transfer\Support\Csv\Reader;

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
        $model = TransferModel::query()->findOrFail($transfer);
        $transfers = app(Transfers::class);
        $reader = new Reader($transfers->disk(), (string) $model->input_path, $model->type->columns());

        try {
            $count = $reader->validate();
        } catch (ValidationException $exception) {
            $model->update([
                'status' => TransferStatus::Failed,
                'finished_at' => now(),
                'report' => ['error' => (string) collect($exception->errors())->flatten()->first(), 'summary' => []],
            ]);

            return ['valid' => false];
        }

        $planner = $transfers->planner($model);
        $requester = $model->requester;
        $actor = $requester instanceof Model ? $requester : null;
        $summary = [];
        $batch = [];

        // Planned rows go to roster_transfer_rows in batches.
        $model->lines()->delete();

        foreach ($reader->rows() as $row) {
            $plan = $planner->plan($row['values'], $model, $actor);
            $summary[$plan['action']] = ($summary[$plan['action']] ?? 0) + 1;
            $batch[] = [
                'transfer_id' => $model->id,
                'line' => $row['line'],
                'values' => (string) json_encode($row['values']),
                'action' => $plan['action'],
                'reasons' => (string) json_encode($plan['reasons']),
            ];

            if (count($batch) === 500) {
                TransferRowModel::query()->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            TransferRowModel::query()->insert($batch);
        }

        ksort($summary);

        $expires = now()->addHours((int) config('roster.transfers.confirm_within_hours', 24));

        $model->update([
            'status' => TransferStatus::AwaitingConfirmation,
            'row_count' => $count,
            'expires_at' => $expires,
            'report' => ['summary' => $summary],
        ]);

        return ['valid' => true, 'expires_at' => $expires->toIso8601String()];
    }
}
