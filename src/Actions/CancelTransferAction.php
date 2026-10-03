<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Validation\ValidationException;
use JayI\Impex\Domains\Run\Models\RunModel;
use JayI\Impex\Impex;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Events\Action\TransferCancelledActionEvent;
use JayI\Roster\Events\Action\TransferCancellingActionEvent;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Transfers;

final class CancelTransferAction
{
    public function __construct(private readonly Transfers $transfers) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Cancel an import awaiting confirmation (nothing was applied), or an
     * import or export still running (rows already applied stay).
     */
    public function execute(Transfer $transfer): Transfer
    {
        $this->transfers->ensureAvailable();

        if ($transfer->status->isFinished()) {
            throw ValidationException::withMessages(['transfer' => __('roster::roster.transfer_finished')]);
        }

        TransferCancellingActionEvent::dispatch($transfer);

        $run = RunModel::query()->find($transfer->impex_run_id);
        $impex = app(Impex::class);

        if ($run instanceof RunModel && $transfer->status === TransferStatus::AwaitingConfirmation) {
            $impex->signal($run, 'confirm', ['confirmed' => false, 'reason' => 'cancelled']);
        } elseif ($run instanceof RunModel) {
            $impex->cancel($run, 'Cancelled in Roster.');
        }

        $transfer->refresh();

        if (! $transfer->status->isFinished()) {
            $transfer->update(['status' => TransferStatus::Cancelled, 'finished_at' => now()]);
        }

        $transfer = $transfer->refresh();

        TransferCancelledActionEvent::dispatch($transfer);

        return $transfer;
    }
}
