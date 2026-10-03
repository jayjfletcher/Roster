<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use JayI\Impex\Domains\Run\Models\RunModel;
use JayI\Impex\Impex;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Events\Action\TransferConfirmedActionEvent;
use JayI\Roster\Events\Action\TransferConfirmingActionEvent;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Transfers;

final class ConfirmImportAction
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
     * Apply a previewed import. The confirmer's permissions are checked again
     * now: an import is applied as them.
     */
    public function execute(Transfer $transfer, ?Model $actor = null): Transfer
    {
        $this->transfers->ensureAvailable();

        if ($transfer->status !== TransferStatus::AwaitingConfirmation) {
            throw ValidationException::withMessages(['transfer' => __('roster::roster.transfer_not_awaiting')]);
        }

        if (! app(Authorizer::class)->check($actor, $transfer->type->permission(), $transfer->organization)) {
            throw ValidationException::withMessages(['transfer' => __('roster::roster.transfer_not_permitted')]);
        }

        TransferConfirmingActionEvent::dispatch($transfer);

        $transfer->update([
            'status' => TransferStatus::Running,
            'confirmed_at' => now(),
            // The confirmer is who the rows are applied as.
            'requested_by' => $actor?->getKey() ?? $transfer->requested_by,
        ]);

        $run = RunModel::query()->findOrFail($transfer->impex_run_id);
        app(Impex::class)->signal($run, 'confirm', ['confirmed' => true]);

        $transfer = $transfer->refresh();

        TransferConfirmedActionEvent::dispatch($transfer);

        return $transfer;
    }
}
