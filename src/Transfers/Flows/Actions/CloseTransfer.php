<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Flows\Actions;

use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Events\Action\TransferFinishedActionEvent;
use JayI\Roster\Models\Transfer;

/**
 * An import that was cancelled, or never confirmed in time.
 */
final class CloseTransfer
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $transfer, string $reason): array
    {
        $model = Transfer::query()->findOrFail($transfer);

        if (! $model->status->isFinished()) {
            $model->update([
                'status' => $reason === 'expired' ? TransferStatus::Expired : TransferStatus::Cancelled,
                'finished_at' => now(),
            ]);

            TransferFinishedActionEvent::dispatch($model->refresh());
        }

        return ['status' => $model->status->value];
    }
}
