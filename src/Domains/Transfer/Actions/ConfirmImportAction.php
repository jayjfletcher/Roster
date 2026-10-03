<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use JayI\Impex\Domains\Run\Models\RunModel;
use JayI\Impex\Impex;
use JayI\Roster\Domains\Permission\Services\Authorizer;
use JayI\Roster\Domains\Transfer\Enums\TransferStatus;
use JayI\Roster\Domains\Transfer\Events\TransferConfirmedActionEvent;
use JayI\Roster\Domains\Transfer\Events\TransferConfirmingActionEvent;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Domains\Transfer\Services\Transfers;

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
    public function execute(TransferModel $transfer, ?Model $actor = null): TransferModel
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
