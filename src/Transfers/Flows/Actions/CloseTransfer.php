<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Flows\Actions;

use JayI\Roster\Domains\Transfer\Enums\TransferStatus;
use JayI\Roster\Domains\Transfer\Events\TransferFinishedActionEvent;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Domains\Transfer\Services\PlanCache;

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
        $model = TransferModel::query()->findOrFail($transfer);

        if (! $model->status->isFinished()) {
            $model->update([
                'status' => $reason === 'expired' ? TransferStatus::Expired : TransferStatus::Cancelled,
                'finished_at' => now(),
            ]);

            app(PlanCache::class)->forget($model->id);
            TransferFinishedActionEvent::dispatch($model->refresh());
        }

        return ['status' => $model->status->value];
    }
}
