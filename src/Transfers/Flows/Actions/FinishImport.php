<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Flows\Actions;

use JayI\Impex\Impex;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Events\Action\TransferFinishedActionEvent;
use JayI\Roster\Models\Transfer;

/**
 * Fold each row's outcome back into the report.
 */
final class FinishImport
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $transfer, string $batch): array
    {
        $model = Transfer::query()->findOrFail($transfer);
        $outcomes = [];

        foreach (app(Impex::class)->batchItems($batch) as $item) {
            $value = $item->result['value'] ?? null;

            if (is_array($value) && isset($value['line'])) {
                $outcomes[(int) $value['line']] = $value;
            } else {
                $line = (int) str_replace('line-', '', (string) $item->item_key);
                $outcomes[$line] = ['line' => $line, 'action' => 'error', 'reasons' => [(string) ($item->error['message'] ?? 'Failed.')]];
            }
        }

        $rows = array_map(function (array $row) use ($outcomes): array {
            $outcome = $outcomes[(int) $row['line']] ?? null;

            return $outcome === null ? $row : array_merge($row, ['result' => $outcome['action'], 'result_reasons' => $outcome['reasons']]);
        }, $model->rows());

        $model->update([
            'status' => TransferStatus::Completed,
            'finished_at' => now(),
            'report' => [
                'summary' => $model->summary(),
                'results' => ValidateImport::count(array_map(fn (array $row): array => ['result' => $row['result'] ?? 'error'], $rows), 'result'),
                'rows' => $rows,
            ],
        ]);

        TransferFinishedActionEvent::dispatch($model->refresh());

        return ['status' => 'completed'];
    }
}
