<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Transfers\Flows\Actions;

use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferFinishedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

final class FinishExport
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $transfer, int $rows): array
    {
        $model = TransferModel::query()->findOrFail($transfer);

        $model->update(['status' => TransferStatus::Completed, 'row_count' => $rows, 'finished_at' => now()]);

        TransferFinishedActionEvent::dispatch($model->refresh());

        return ['status' => 'completed', 'rows' => $rows];
    }
}
