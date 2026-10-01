<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Flows\Actions;

use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Events\Action\TransferFinishedActionEvent;
use JayI\Roster\Models\Transfer;

final class FinishExport
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $transfer, int $rows): array
    {
        $model = Transfer::query()->findOrFail($transfer);

        $model->update(['status' => TransferStatus::Completed, 'row_count' => $rows, 'finished_at' => now()]);

        TransferFinishedActionEvent::dispatch($model->refresh());

        return ['status' => 'completed', 'rows' => $rows];
    }
}
